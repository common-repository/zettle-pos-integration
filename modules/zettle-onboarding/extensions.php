<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding;

use Inpsyde\StateMachine\Event\ListenerProvider;
use Inpsyde\StateMachine\Event\PostTransition;
use Inpsyde\StateMachine\Event\StateAwareListenerProvider;
use Inpsyde\StateMachine\Event\TransitionAwareListenerProvider;
use Inpsyde\StateMachine\StateMachineInterface;
use Inpsyde\WcEvents\Toggle;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Organization\OrganizationProvider;
use Inpsyde\Zettle\Sync\Job\EnqueueProductSyncJob;
use Inpsyde\Zettle\Sync\Job\ExportProductJob;
use Inpsyde\Zettle\Sync\Job\WipeRemoteProductsJob;
use Psr\Container\ContainerInterface as C;

return [
    'zettle.init-possible' => static function (C $ctr, bool $previous): bool {
        $initableStates = [
            OnboardingState::SYNC_PARAM_PRODUCTS,
            OnboardingState::SYNC_PARAM_VAT,
            OnboardingState::SYNC_PROGRESS,
            OnboardingState::SYNC_FINISHED,
            OnboardingState::ONBOARDING_COMPLETED,
        ];

        $stateMachine = $ctr->get('inpsyde.state-machine');

        return in_array($stateMachine->currentState()->name(), $initableStates, true);
    },
    'zettle.sdk.dal.provider.organization' => static function (
        C $container,
        OrganizationProvider $previous
    ): OrganizationProvider {
        $preSyncStates = $container->get('zettle.onboarding.settings-states');

        $stateMachine = $container->get('inpsyde.state-machine');

        // clear cache, to always use the current account settings during setup steps
        if (in_array($stateMachine->currentState()->name(), $preSyncStates, true)) {
            $container->get('zettle.clear-cache')();
        }

        return $previous;
    },
    'inpsyde.state-machine.namespace' => static function (C $container, string $previous): string {
        return 'zettle.onboarding';
    },
    'zettle.settings.fields' => static function (C $container, array $previous): array {
        $filter = $container->get('zettle.onboarding.settings.filter');

        return [
                'onboarding' => [
                    'title' => __('Installation', 'zettle-pos-integration'),
                    'type' => 'zettle-onboarding',
                    'description' => __(
                        'We will guide you through the initial configuration of the PayPal Zettle integration',
                        'zettle-pos-integration'
                    ),
                    'desc_tip' => true,
                    'default' => '',
                ],
            ] + $filter->filter($previous);
    },
    'zettle.settings.field-renderers' => static function (C $container, array $previous): array {
        $previous[] = $container->get('zettle.onboarding.settings.renderer.removed');
        $previous[] = $container->get('zettle.onboarding.settings.renderer.hidden');
        $previous[] = $container->get('zettle.onboarding.settings.renderer.password');
        $previous[] = $container->get('zettle.onboarding.settings.renderer.onboarding');

        return $previous;
    },
    'inpsyde.queue.rest.v1.endpoint.meta-callback' =>
        static function (C $container, callable $previous): callable {
            return static function (array $meta, array $types = []) use ($container, $previous): array {
                $previous = $previous();

                if (!isset($meta['zettle-onboarding'])) {
                    return $previous;
                }
                $phase = $meta['phase'];
                $jobRepo = $container->get('inpsyde.queue.repository');
                $jobTypes = [
                    'prepare' => [
                        EnqueueProductSyncJob::TYPE,
                        WipeRemoteProductsJob::TYPE,
                    ],
                    'sync' => [
                        ExportProductJob::TYPE,
                    ],
                    'cleanup' => [],

                ];
                $result = $jobRepo->fetch(1, $jobTypes[$phase]);

                return array_merge(
                    [
                        'isFinished' => empty($result),
                    ],
                    $previous
                );
            };
        },
    'inpsyde.state-machine.events.listener-provider.state-aware' => static function (
        C $container,
        StateAwareListenerProvider $listenerProvider
    ): StateAwareListenerProvider {
        foreach (
            $container->get(
                'zettle.onboarding.state-machine.actions'
            ) as $sourceState => $listeners
        ) {
            foreach ((array) $listeners as $listener) {
                $listenerProvider->listen($sourceState, $listener);
            }
        }

        return $listenerProvider;
    },
    'inpsyde.state-machine.events.listener-provider.transition-aware' => static function (
        C $container,
        TransitionAwareListenerProvider $listenerProvider
    ): TransitionAwareListenerProvider {
        foreach (
            $container->get(
                'zettle.onboarding.state-machine.transition-events'
            ) as $transitionName => $listeners
        ) {
            foreach ((array) $listeners as $listener) {
                $listenerProvider->listen($transitionName, $listener);
            }
        }

        return $listenerProvider;
    },
    'inpsyde.state-machine.events.listener-provider.internal' => static function (
        C $container,
        ListenerProvider $listenerProvider
    ): ListenerProvider {
        $setState = $container->get('zettle.onboarding.set-state');
        assert(is_callable($setState));

        $listenerProvider->addListener(
            static function (PostTransition $event) use ($setState) {
                $setState($event->transition()->toState());
            }
        );

        $listenerProvider->addListener(
            $container->get('zettle.onboarding.repository.auth-check-event')
        );

        $listenerProvider->addListener(
            $container->get('zettle.onboarding.repository.auth-failed-event')
        );

        $listenerProvider->addListener(
            $container->get('zettle.onboarding.repository.unhandled-error-event')
        );

        return $listenerProvider;
    },
    /**
     * We don't want the queue to process anything if onboarding has not yet completed.
     * In that case, we simply pass an empty array as the available queue runners
     */
    'inpsyde.queue.runners' => static function (C $container, array $previous): array {
        $currentState = $container->get('inpsyde.state-machine')->currentState()->name();

        if ($currentState === OnboardingState::ONBOARDING_COMPLETED) {
            return $previous;
        }

        return [];
    },
    'zettle.assets.should-enqueue.all' =>
        static function (C $container, callable $previous): callable {
            return static function () use ($previous, $container): bool {
                return $previous() and $container->get('zettle.settings.is-integration-page')();
            };
        },
    'zettle.assets.should-enqueue.sync-module' =>
        static function (C $container, callable $previous): callable {
            return static function () use ($previous, $container): bool {
                if (!$previous()) {
                    return false;
                }
                $stateMachine = $container->get('inpsyde.state-machine');
                assert($stateMachine instanceof StateMachineInterface);

                return $stateMachine->currentState()->name() === OnboardingState::SYNC_PROGRESS;
            };
        },
    'inpsyde.wc-lifecycle-events.products.toggle' =>
        static function (C $container, Toggle $toggle): Toggle {
            $currentState = $container->get('inpsyde.state-machine')->currentState()->name();

            if ($currentState === OnboardingState::ONBOARDING_COMPLETED) {
                return $toggle;
            }
            $toggle->disable();

            return $toggle;
        },
];
