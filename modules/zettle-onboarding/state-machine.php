<?php // phpcs:disable - There is a weird error on PHP7.4 which breaks phpcs when returning $_POST down below
declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding;

use Dhii\Data\Container\WritableContainerInterface;
use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\StateMachine\Event\PostTransition;
use Inpsyde\StateMachine\State\State;
use Inpsyde\StateMachine\State\StateInterface;
use Inpsyde\StateMachine\Transition\Transition;
use Inpsyde\Zettle\Auth\OAuth\CredentialValidator;
use Inpsyde\Zettle\Auth\Validator\Validator;
use Inpsyde\Zettle\Onboarding\Event\AuthCheck;
use Inpsyde\Zettle\Onboarding\Event\AuthFailed;
use Inpsyde\Zettle\Onboarding\Event\BackButtonPressed;
use Inpsyde\Zettle\Onboarding\Event\CancelButtonPressed;
use Inpsyde\Zettle\Onboarding\Event\DeleteButtonPressed;
use Inpsyde\Zettle\Onboarding\Event\ProceedButtonPressed;
use Inpsyde\Zettle\Onboarding\Event\UnhandledError;
use Inpsyde\Zettle\Onboarding\Job\ResetOnboardingJob;
use Inpsyde\Zettle\Onboarding\OnboardingState as S;
use Inpsyde\Zettle\Onboarding\OnboardingTransition as T;
use Inpsyde\Zettle\PhpSdk\Exception\ZettleRestException;
use Inpsyde\Zettle\Sync\Job\EnqueueProductSyncJob;
use Inpsyde\Zettle\Sync\Job\WipeRemoteProductsJob;
use Inpsyde\Zettle\Sync\PriceSyncMode;
use Psr\Container\ContainerInterface as C;
use Psr\Log\LoggerInterface;
use Throwable;

return [
    /**
     * This is a very simple list of all available states in the State Machine
     */
    'zettle.onboarding.states' => static function (C $container): array {
        return [
            new State(S::WELCOME, State::TYPE_INITIAL),
            new State(S::API_CREDENTIALS),
            new State(S::INVALID_CREDENTIALS),
            new State(S::SYNC_PARAM_PRODUCTS),
            new State(S::SYNC_PARAM_VAT),
            new State(S::SYNC_PROGRESS),
            new State(S::SYNC_FINISHED),
            new State(S::ONBOARDING_COMPLETED),
            new State(S::UNHANDLED_ERROR),
        ];
    },
    'zettle.onboarding.states.names' => static function (C $container): array {
        return array_map(
            static function (StateInterface $state): string {
                return $state->name();
            },
            $container->get('zettle.onboarding.states')
        );
    },
    /**
     * This is a list of possible Transitions that each state can perform. A Transition consists of
     * 1. The name of the Transition
     * 2. An array of states that are capable of moving towards the target state
     * 3. The name of the target state
     */
    'zettle.onboarding.transitions' => static function (C $container): array {
        return [
            new Transition(
                T::TO_API_CREDENTIALS,
                [
                    S::WELCOME,
                    S::SYNC_PARAM_PRODUCTS,
                    S::INVALID_CREDENTIALS,
                ],
                S::API_CREDENTIALS
            ),
            new Transition(
                T::TO_WELCOME,
                [
                    S::API_CREDENTIALS,
                    S::UNHANDLED_ERROR,
                ],
                S::WELCOME
            ),
            new Transition(
                T::TO_INVALID_CREDENTIALS,
                [
                    S::API_CREDENTIALS,
                    S::SYNC_PARAM_PRODUCTS,
                    S::SYNC_PARAM_VAT,
                    S::SYNC_PROGRESS,
                ],
                S::INVALID_CREDENTIALS
            ),
            new Transition(
                T::TO_SYNC_PARAM_PRODUCTS,
                [
                    S::API_CREDENTIALS,
                    S::SYNC_PARAM_VAT,
                    S::SYNC_PROGRESS,
                ],
                S::SYNC_PARAM_PRODUCTS
            ),
            new Transition(
                T::TO_SYNC_PARAM_VAT,
                [
                    S::SYNC_PARAM_PRODUCTS,
                    S::SYNC_PROGRESS,
                ],
                S::SYNC_PARAM_VAT
            ),
            new Transition(T::TO_SYNC_PROGRESS, [S::SYNC_PARAM_VAT], S::SYNC_PROGRESS),
            new Transition(
                T::TO_SYNC_FINISHED,
                [S::SYNC_PROGRESS],
                S::SYNC_FINISHED
            ),
            new Transition(
                T::TO_ONBOARDING_COMPLETED,
                [S::SYNC_FINISHED],
                S::ONBOARDING_COMPLETED
            ),
            new Transition(
                T::TO_UNHANDLED_ERROR,
                array_diff(
                    $container->get('zettle.onboarding.states.names'),
                    [
                        S::UNHANDLED_ERROR,
                        S::ONBOARDING_COMPLETED,
                    ]
                ),
                S::UNHANDLED_ERROR
            ),
        ];
    },
    /**
     * These are the Actions that should produce a new state by inspecting the Event data
     * and then writing the target state to the Event via the 'transitionTo()' method.
     * Under the hood, these are all PSR-14-compliant event listeners that get added to a special
     * ListenerProvider that delegates the event to the listeners depending on the source state
     * (which is the array key) and the event type (can be any sub/class or interface as long as it
     * implements the StateChange interface)
     */
    'zettle.onboarding.state-machine.actions' => static function (C $container): array {
        /**
         * Helper Function to validate if the field is set and not empty
         *
         * @param string $id
         * @param array $data
         *
         * @return bool
         */
        $validateField = static function (string $id, array $data): bool {
            return isset($data[$id]) && !empty($data[$id]);
        };

        return [
            /**
             * Start Onboarding flow if the proceed button is pressed
             * -> Move to App Credentials
             */
            S::WELCOME => static function (ProceedButtonPressed $event) {
                $event->transitionTo(S::API_CREDENTIALS);
            },
            S::API_CREDENTIALS => [
                /**
                 * Move back to Welcome to start over entering credentials
                 */
                static function (BackButtonPressed $event) {
                    $event->transitionTo(S::WELCOME);
                },
                static function (ProceedButtonPressed $event) use ($container, $validateField) {
                    $data = $event->data();

                    $addErrorMessageCallback = $container->get('zettle.onboarding.message.add.error');

                    if (!$validateField('woocommerce_zettle_api_key', $data)) {
                        ($addErrorMessageCallback)(
                            esc_html__(
                                "Please enter a valid API key or create a new API Key, if you don't have one already.",
                                'zettle-pos-integration'
                            )
                        );

                        $event->transitionTo(S::INVALID_CREDENTIALS);

                        return;
                    }
                    $token = $data['woocommerce_zettle_api_key'];

                    $settings = $container->get('zettle.settings');

                    // already entered the key, then returned back to this step and proceeded without changing it
                    // (write-only password input not exposing the saved value)
                    if (
                        $settings->has('api_key') && !empty($settings->get('api_key'))
                        && !$container->get('zettle.onboarding.settings.write-only-password-field-checker')($token)
                    ) {
                        $event->transitionTo(S::SYNC_PARAM_PRODUCTS);
                        return;
                    }

                    $jwtValidator = $container->get('zettle.oauth.jwt.validator');
                    assert($jwtValidator instanceof Validator);
                    $credentialValidator = $container->get('zettle.oauth.credential-validator');
                    assert($credentialValidator instanceof CredentialValidator);
                    if (!($jwtValidator->validate($token) && $credentialValidator->validateApiToken($token))) {
                        ($addErrorMessageCallback)(
                            esc_html__(
                                'The API key you entered is invalid. Try again or create a new API key if the problem still occurs.',
                                'zettle-pos-integration'
                            )
                        );

                        $event->transitionTo(S::INVALID_CREDENTIALS);

                        return;
                    }

                    $event->transitionTo(S::SYNC_PARAM_PRODUCTS);
                },
            ],
            S::INVALID_CREDENTIALS => [
                /**
                 * Start over Button: Move back to Credentials to start over entering credentials
                 */
                static function (ProceedButtonPressed $event) use ($container) {
                    $settings = $container->get('zettle.settings');

                    if ($settings->has('api_key')) {
                        $settings->set('api_key', '');
                    }

                    //phpcs:disable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
                    //phpcs:disable WordPress.Security.NonceVerification.Missing
                    if (isset($_POST['woocommerce_zettle_api_key'])) {
                        unset($_POST['woocommerce_zettle_api_key']);
                    }

                    // TODO: Delete Organization transient

                    $event->transitionTo(S::API_CREDENTIALS);
                },
            ],
            S::SYNC_PARAM_PRODUCTS => [
                /**
                 * Move back
                 */
                static function (BackButtonPressed $event) {
                    $event->transitionTo(S::API_CREDENTIALS);
                },
                /**
                 * Move to SYNC_PARAM_VAT
                 */
                static function (ProceedButtonPressed $event) {
                    $data = $event->data();

                    if (!isset($data['woocommerce_zettle_sync_collision_strategy'])) {
                        return;
                    }

                    $event->transitionTo(S::SYNC_PARAM_VAT);
                },
            ],
            S::SYNC_PARAM_VAT => [
                /**
                 * Move back
                 */
                static function (BackButtonPressed $event) {
                    $event->transitionTo(S::SYNC_PARAM_PRODUCTS);
                },
                /**
                 * With all information gathered, we can proceed to the review screen
                 */
                static function (ProceedButtonPressed $event) use ($container) {
                    $data = $event->data();
                    $syncTaxStrategy = $data['woocommerce_zettle_sync_price_strategy'] ?? null;
                    $addErrorMessage = $container->get('zettle.onboarding.message.add.error');

                    if (!$syncTaxStrategy) {
                        $addErrorMessage(
                            __('Missing price sync configuration!', 'zettle-pos-integration')
                        );

                        return;
                    }

                    $storeComparison = $container->get('zettle.onboarding.comparison.store');

                    if ($syncTaxStrategy === PriceSyncMode::ENABLED && !$storeComparison->currency()) {
                        $addErrorMessage(
                            wp_kses_post(
                                __('Invalid price sync configuration!', 'zettle-pos-integration')
                            )
                        );

                        return;
                    }

                    $event->transitionTo(S::SYNC_PROGRESS);
                },
            ],
            S::SYNC_PROGRESS => [
                /**
                 * Move back to SYNC_PARAM_VAT
                 * TODO: Needed? Most definitely not
                 */
                static function (BackButtonPressed $event) {
                    $event->transitionTo(OnboardingState::SYNC_PARAM_VAT);
                },
                /**
                 * The button will be kept disabled until the JS workers have completed
                 * syncing via REST calls. Then we can simply move on.
                 */
                static function (ProceedButtonPressed $event) {
                    $event->transitionTo(S::SYNC_FINISHED);
                },
                /**
                 * If the cancel button was pressed, the user likely wants to
                 * revisit all sync params
                 */
                static function (CancelButtonPressed $event) {
                    $event->transitionTo(S::SYNC_PARAM_PRODUCTS);
                },
            ],
            /** Oh boy we're finally done. */
            S::SYNC_FINISHED => static function (ProceedButtonPressed $event) {
                $event->transitionTo(S::ONBOARDING_COMPLETED);
            },
            S::ONBOARDING_COMPLETED => [
            ],
            S::UNHANDLED_ERROR => [
                static function (DeleteButtonPressed $event) {
                    $event->transitionTo(S::WELCOME);
                },
            ],
        ];
    },
    /**
     * Listeners for Pre/Post Transition events go here. They work similar to the state actions:
     * The array key is the transition name and the listener signature
     * determines whether to listen to a PreTransition or PostTransition event
     */
    'zettle.onboarding.state-machine.transition-events' => static function (C $container): array {
        return [
            T::TO_API_CREDENTIALS => static function (PostTransition $event) use ($container) {
                $tokenStorage = $container->get('zettle.oauth.token-storage');
                $tokenStorage->clear();
            },
            T::TO_SYNC_PARAM_PRODUCTS => static function (PostTransition $event) use ($container) {
                if ($event->fromState() === S::SYNC_PROGRESS) {
                    /**
                     * Sync was cancelled and the user will now re-enter the sync params.
                     * Flush all jobs in the queue so we can start fresh.
                     */
                    $queueRepository = $container->get('inpsyde.queue.repository');
                    assert($queueRepository instanceof JobRepository);
                    $queueRepository->flush();
                }
            },
            /**
             * Enqueue our synchronization background jobs when we start syncing
             */
            T::TO_SYNC_PROGRESS => static function (PostTransition $event) use ($container) {
                $enqueue = $container->get('inpsyde.queue.enqueue-job');
                $settings = $container->get('zettle.settings');

                if ($settings->has('sync_collision_strategy')) {
                    $collisionStrategy = $settings->get('sync_collision_strategy');

                    if ($collisionStrategy === SyncCollisionStrategy::WIPE) {
                        $enqueue(WipeRemoteProductsJob::TYPE);
                    }
                }

                $enqueue(EnqueueProductSyncJob::TYPE);

                $setupInfo = $container->get('zettle.setup-info');

                $setupInfo->set('first_import_timestamp', time());
            },
            T::TO_ONBOARDING_COMPLETED => static function (PostTransition $event) use ($container) {
                $webhookRegistration = $container->get('zettle.webhook.register');

                assert(is_callable($webhookRegistration));

                try {
                    $webhookRegistration();
                } catch (ZettleRestException $exception) {
                    $logger = $container->get('zettle.logger.woocommerce');
                    assert($logger instanceof LoggerInterface);

                    $logger->error($exception->getMessage());

                    $addErrorMessageCallback = $container->get('zettle.onboarding.message.add.error');

                    $addErrorMessageCallback(
                        wp_kses_post($container->get('zettle.onboarding.error.message.webhooks'))
                    );
                }
            },
            /**
             * Destroy everything when we disconnect the account
             */
            T::TO_WELCOME => static function (PostTransition $event) use ($container) {
                if (!in_array($event->fromState(), [S::UNHANDLED_ERROR], true)) {
                    return;
                }
                /**
                 * Execute the single job directly instead of processing the queue manually:
                 * The latter could die in a QueueLockedException
                 */
                $job = $container->get('zettle.job.' . ResetOnboardingJob::TYPE);
                assert($job instanceof Job);

                try {
                    $job->execute(
                        Context::fromArray([]),
                        new EphemeralJobRepository(),
                        $container->get('zettle.logger.woocommerce')
                    );
                } catch (Throwable $exception) {
                    //silence
                }
            },
        ];
    },
    'zettle.onboarding.repository.auth-check-event' => static function (C $container): callable {
        return static function (AuthCheck $event) use ($container) {
            $notAllowedStates = $container->get('zettle.onboarding.auth.failure.not-allowed-states');
            if (in_array($event->currentState(), $notAllowedStates, true)) {
                return;
            }

            if ($container->get('zettle.sdk.api.auth-check')()) {
                return;
            }

            $event->transitionTo(S::INVALID_CREDENTIALS);
        };
    },
    'zettle.onboarding.repository.auth-failed-event' => static function (C $container): callable {
        return static function (AuthFailed $event) use ($container) {
            $notAllowedStates = $container->get('zettle.onboarding.auth.failure.not-allowed-states');
            if (in_array($event->currentState(), $notAllowedStates, true)) {
                return;
            }

            $event->transitionTo(S::INVALID_CREDENTIALS);
        };
    },
    'zettle.onboarding.repository.unhandled-error-event' =>
        static function (C $container): callable {
            return static function (UnhandledError $event) use ($container) {
                $notAllowedStates = $container->get('zettle.onboarding.failure.excluded-states');
                if (in_array($event->currentState(), $notAllowedStates, true)) {
                    return;
                }

                $event->transitionTo(S::UNHANDLED_ERROR);
            };
        },
];
