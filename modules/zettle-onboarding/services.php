<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding;

use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\StateMachine\StateMachineInterface;
use Inpsyde\Zettle\Onboarding\Cli\ResetOnboardingCommand;
use Inpsyde\Zettle\Onboarding\Comparison\StoreComparison;
use Inpsyde\Zettle\Onboarding\Counter\ProductSyncJobsCounter;
use Inpsyde\Zettle\Onboarding\DataProvider\Store\StoreDataProvider;
use Inpsyde\Zettle\Onboarding\DataProvider\Store\WooCommerceStoreDataProvider;
use Inpsyde\Zettle\Onboarding\DataProvider\Store\ZettleStoreDataProvider;
use Inpsyde\Zettle\Onboarding\Job\ResetOnboardingJob;
use Inpsyde\Zettle\Onboarding\Listener\UnhandledErrorListener;
use Inpsyde\Zettle\Onboarding\Provider\ErrorListenerProvider;
use Inpsyde\Zettle\Onboarding\Provider\OnboardingRenderProvider;
use Inpsyde\Zettle\Onboarding\Provider\ResetCommandProvider;
use Inpsyde\Zettle\Onboarding\Provider\StateMachineProvider;
use Inpsyde\Zettle\Onboarding\Rest\DisconnectEndpoint;
use Inpsyde\Zettle\Onboarding\Rest\EndpointInterface;
use Inpsyde\Zettle\Onboarding\Settings\FieldRenderer\HiddenFieldRenderer;
use Inpsyde\Zettle\Onboarding\Settings\FieldRenderer\OnboardingFieldRenderer;
use Inpsyde\Zettle\Onboarding\Settings\FieldRenderer\RemovedFieldRenderer;
use Inpsyde\Zettle\Onboarding\Settings\FieldRenderer\WriteOnlyPasswordFieldRenderer;
use Inpsyde\Zettle\Onboarding\Settings\Filter\GenericSettingsValueFilter;
use Inpsyde\Zettle\Onboarding\Settings\Filter\OnboardingProcessFilter;
use Inpsyde\Zettle\Onboarding\Settings\Filter\SettingsFilter;
use Inpsyde\Zettle\Onboarding\Settings\Filter\SettingsValueFilter;
use Inpsyde\Zettle\Onboarding\Settings\OnboardingStepper;
use Inpsyde\Zettle\Onboarding\Settings\View\ContainerAwareView;
use Inpsyde\Zettle\Onboarding\Settings\View\OnboardingCompletedView;
use Inpsyde\Zettle\Onboarding\Settings\View\OnboardingView;
use Inpsyde\Zettle\Onboarding\Settings\View\ProductSyncParamView;
use Inpsyde\Zettle\Onboarding\Settings\View\SyncProgressView;
use Inpsyde\Zettle\Onboarding\Settings\View\SyncVatParamView;
use Inpsyde\Zettle\Onboarding\Settings\WriteOnlyPasswordFieldChecker;
use Inpsyde\Zettle\PhpSdk\API\Products\Products;
use Inpsyde\Zettle\PhpSdk\Exception\ZettleRestException;
// phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
use Inpsyde\Zettle\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface as WcProductRepositoryInterface;
use Inpsyde\Zettle\Provider;
use Inpsyde\Zettle\Settings\FieldRenderer\FieldRendererInterface;
use Psr\Container\ContainerInterface as C;
use WC_Admin_Settings;
use wpdb;

// phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong

$job = static function (string $type): string {
    return "zettle.job.{$type}";
};

return [
    'zettle.onboarding.wpdb' => static function (C $container): wpdb {
        global $wpdb;

        return $wpdb;
    },
    'zettle.onboarding.option.state' => static function (C $container): string {
        return 'onboarding.current-state';
    },
    'zettle.onboarding.initial-state' => static function (C $container): string {
        $optionContainer = $container->get('zettle.settings');
        $key = $container->get('zettle.onboarding.option.state');
        if (!$optionContainer->has($key)) {
            return '';
        }

        return $optionContainer->get($key);
    },
    'zettle.onboarding.set-state' => static function (C $container): callable {
        return static function (string $state) use ($container) {
            $container->get('zettle.settings')->set(
                $container->get('zettle.onboarding.option.state'),
                $state
            );
        };
    },
    'zettle.onboarding.current-state' => static function (C $container): string {
        $stateMachine = $container->get('inpsyde.state-machine');
        assert($stateMachine instanceof StateMachineInterface);

        return $stateMachine->currentState()->name();
    },
    'zettle.onboarding.comparison.store.remote' =>
        static function (C $container): StoreDataProvider {
            return new ZettleStoreDataProvider(
                $container->get('zettle.sdk.dal.provider.organization')
            );
        },
    'zettle.onboarding.comparison.store.local' =>
        static function (C $container): StoreDataProvider {
            return new WooCommerceStoreDataProvider(
                $container->get('zettle.wc.shop.location'),
                $container->get('zettle.wc.tax.standard-rates')
            );
        },
    'zettle.onboarding.comparison.store' => static function (C $container): StoreComparison {
        return new StoreComparison(
            $container->get('zettle.onboarding.comparison.store.remote'),
            $container->get('zettle.onboarding.comparison.store.local')
        );
    },
    'zettle.onboarding.zettle-link' => static function (C $container): array {
        return [
            'title' => __('Zettle.com', 'zettle-pos-integration'),
            'url' => __('https://zettle.com/', 'zettle-pos-integration'),
        ];
    },
    'zettle.onboarding.documentation-link' => static function (C $container): array {
        return [
            'title' => __('Documentation - Supported Product Types', 'zettle-pos-integration'),
            'url' => __(
                'https://woocommerce.com/document/paypal-zettle-pos-for-woocommerce/#what-are-the-requirements-for-woocommerce-products-to-be-synchronized-with-paypal-zettle',
                'zettle-pos-integration'
            ),
        ];
    },
    'zettle.onboarding.zettle-products-link' => static function (C $container): array {
        return [
            'title' => __('Go to PayPal Zettle product library', 'zettle-pos-integration'),
            'url' => __('https://my.zettle.com/products', 'zettle-pos-integration'),
        ];
    },
    'zettle.onboarding.support-link' => static function (C $container): array {
        return [
            'title' => __('PayPal Zettle Support', 'zettle-pos-integration'),
            'url' => __('https://zettle.com/', 'zettle-pos-integration'),
        ];
    },
    'zettle.onboarding.full-settings-link' => static function (C $container): array {
        return [
            'title' => __('Show settings', 'zettle-pos-integration'),
            'url' => add_query_arg(
                [
                    'review' => true,
                ],
                $container->get('zettle.settings.url')
            ),
        ];
    },
    'zettle.onboarding.error.message.webhooks' => static function (C $container): string {
        return __(
            'Registration of PayPal Zettle webhooks has failed, product stock changes from PayPal Zettle will not apply.
            Please check WC logs and reach out the support.
            You can repeat webhooks registration by deactivating and activating the plugin in the plugins list.',
            'zettle-pos-integration'
        );
    },
    'zettle.onboarding.settings.view.product-sync-params' =>
        static function (C $container): OnboardingView {
            return new ProductSyncParamView(
                $container->get('zettle.onboarding.count.products.local'),
                $container->get('zettle.onboarding.count.products.local.total'),
                $container->get('zettle.onboarding.count.products.remote'),
                $container->get('zettle.onboarding.documentation-link')
            );
        },
    'zettle.onboarding.settings.view.sync-vat-param' =>
        static function (C $container): OnboardingView {
            return new SyncVatParamView(
                $container->get('zettle.onboarding.comparison.store'),
                $container->get('zettle.onboarding.comparison.store.remote'),
                $container->get('zettle.onboarding.comparison.store.local'),
                $container->get('zettle.sdk.default-taxes')
            );
        },
    'zettle.onboarding.settings.view.sync-progress' =>
        static function (C $container): OnboardingView {
            return new SyncProgressView(
                $container->get('zettle.onboarding.count.products.local'),
                $container->get('zettle.onboarding.count.products.local.total'),
                $container->get('zettle.plugin.properties')
            );
        },
    'zettle.onboarding.settings.view.onboarding-completed' =>
        static function (C $container): OnboardingView {
            return new OnboardingCompletedView(
                $container->get('zettle.onboarding.zettle-products-link'),
                $container->get('zettle.onboarding.full-settings-link')
            );
        },
    'zettle.onboarding.settings.renderer.onboarding.current' =>
        static function (C $container): OnboardingView {
            return new ContainerAwareView($container);
        },
    'zettle.onboarding.settings.renderer.hidden' =>
        static function (C $container): FieldRendererInterface {
            return new HiddenFieldRenderer();
        },
    'zettle.onboarding.settings.renderer.removed' =>
        static function (C $container): FieldRendererInterface {
            return new RemovedFieldRenderer();
        },
    'zettle.onboarding.settings.renderer.password' =>
        static function (C $container): FieldRendererInterface {
            return new WriteOnlyPasswordFieldRenderer();
        },

    'zettle.onboarding.settings.stepper.exclude' => static function (C $container): array {
        return [
            OnboardingState::WELCOME,
            OnboardingState::INVALID_CREDENTIALS,
            OnboardingState::SYNC_FINISHED,
            OnboardingState::ONBOARDING_COMPLETED,
        ];
    },
    'zettle.onboarding.settings.stepper' => static function (C $container): OnboardingStepper {
        return new OnboardingStepper(
            $container->get('zettle.onboarding.states'),
            $container->get('inpsyde.state-machine')->currentState()->name(),
            $container->get('zettle.onboarding.settings.stepper.exclude'),
            __('Step', 'zettle-pos-integration')
        );
    },
    'zettle.onboarding.settings.renderer.onboarding' =>
        static function (C $container): FieldRendererInterface {
            $stateMachine = $container->get('inpsyde.state-machine');

            return new OnboardingFieldRenderer(
                $stateMachine->currentState()->name(),
                $container->get('zettle.onboarding.settings.renderer.onboarding.current'),
                $container->get('zettle.onboarding.settings.stepper'),
                $container->get('zettle.settings.is-integration-page')
            );
        },
    'zettle.onboarding.settings.filter' => static function (
        C $container
    ): SettingsFilter {
        $stateMachine = $container->get('inpsyde.state-machine');
        assert($stateMachine instanceof StateMachineInterface);

        return new OnboardingProcessFilter(
            $stateMachine->currentState()->name()
        );
    },
    'zettle.onboarding.settings.write-only-password-field-checker' => static function (
        C $container
    ): callable {
        return new WriteOnlyPasswordFieldChecker(
            $container->get('zettle.onboarding.settings.write-only-password-field-checker.placeholder.char'),
            $container->get('zettle.onboarding.settings.write-only-password-field-checker.placeholder.max-length')
        );
    },
    'zettle.onboarding.settings.write-only-password-field-checker.placeholder.char' => static function (
        C $container
    ): string {
        return '*';
    },
    'zettle.onboarding.settings.write-only-password-field-checker.placeholder.max-length' => static function (
        C $container
    ): int {
        return 15;
    },
    'zettle.onboarding.settings.value-filter.api-key' => static function (
        C $container
    ): SettingsValueFilter {
        return new GenericSettingsValueFilter(
            'api_key',
            $container->get('zettle.onboarding.settings.write-only-password-field-checker')
        );
    },
    'zettle.onboarding.pre-auth-states' => static function (C $container): array {
        return [
            OnboardingState::WELCOME,
            OnboardingState::API_CREDENTIALS,
            OnboardingState::INVALID_CREDENTIALS,
        ];
    },
    'zettle.onboarding.no-auth-states' => static function (C $container): array {
        return array_merge(
            $container->get('zettle.onboarding.pre-auth-states'),
            [
                OnboardingState::UNHANDLED_ERROR,
            ]
        );
    },
    'zettle.onboarding.api-auth-check' => static function (C $container): callable {
        return static function () use ($container): bool {
            $notAllowedStates = (array) $container->get('zettle.onboarding.no-auth-states');

            $stateMachine = $container->get('inpsyde.state-machine');
            assert($stateMachine instanceof StateMachineInterface);

            if (in_array($stateMachine->currentState()->name(), $notAllowedStates, true)) {
                return false;
            }

            return $container->get('zettle.sdk.api.auth-check')();
        };
    },
    'zettle.onboarding.auth.failure.not-allowed-states' => static function (C $container): array {
        return array_merge(
            $container->get('zettle.onboarding.no-auth-states'),
            [
                OnboardingState::SYNC_FINISHED, // doesn't make sense to go back after sync
                OnboardingState::ONBOARDING_COMPLETED,
            ]
        );
    },
    'zettle.onboarding.failure.excluded-states' => static function (C $container): array {
        return [
            OnboardingState::ONBOARDING_COMPLETED,
            OnboardingState::UNHANDLED_ERROR,
        ];
    },
    'zettle.onboarding.failure.listener' =>
        static function (C $container): UnhandledErrorListener {
            return new UnhandledErrorListener(
                $container->get('inpsyde.state-machine'),
                $container->get('zettle.http.page-reloader'),
                $container->get('zettle.throw-unhandled-errors')
            );
        },
    'zettle.onboarding.settings-states' => static function (): array {
        return [
            OnboardingState::API_CREDENTIALS,
            OnboardingState::SYNC_PARAM_PRODUCTS,
            OnboardingState::SYNC_PARAM_VAT,
        ];
    },
    'zettle.onboarding.collector.products.local' => static function (C $container): callable {
        return static function (array $types = ['simple', 'variable']) use ($container): array {
            $repository = $container->get('zettle.sdk.repository.woocommerce.product');
            assert($repository instanceof WcProductRepositoryInterface);

            $products = $repository->fetchFromTypes($types);

            $isSyncable = $container->get('zettle.sync.product.sync-active-for-id');
            assert(is_callable($isSyncable));

            return array_filter($products, $isSyncable);
        };
    },
    'zettle.onboarding.count.products.local' => static function (C $container): callable {
        return static function () use ($container): int {
            return count($container->get('zettle.onboarding.collector.products.local')());
        };
    },
    'zettle.onboarding.collector.products.local.total' =>
        static function (C $container): callable {
            return static function () use ($container): array {
                $repository = $container->get('zettle.sdk.repository.woocommerce.product');
                assert($repository instanceof WcProductRepositoryInterface);

                return $repository->fetch();
            };
        },
    'zettle.onboarding.count.products.local.total' => static function (C $container): callable {
        return static function () use ($container): int {
            return count($container->get('zettle.onboarding.collector.products.local.total')());
        };
    },
    'zettle.onboarding.collector.products.remote' => static function (C $container): array {
        $products = $container->get('zettle.sdk.api.products');
        assert($products instanceof Products);

        try {
            $productCollection = $products->list();

            return $productCollection->all();
        } catch (ZettleRestException $exception) {
            return [];
        }
    },
    'zettle.onboarding.count.products.remote' => static function (C $container): callable {
        return static function () use ($container): int {
            return count($container->get('zettle.onboarding.collector.products.remote'));
        };
    },
    'zettle.onboarding.counter.product.sync' =>
        static function (C $container): ProductSyncJobsCounter {
            $settings = $container->get('zettle.settings');
            $syncStrategy = 'add';

            if ($settings->has('sync_collision_strategy')) {
                $syncStrategy = $settings->get('sync_collision_strategy');
            }

            return new ProductSyncJobsCounter(
                $container->get('zettle.onboarding.collector.products.local'),
                $syncStrategy
            );
        },
    'zettle.onboarding.message.add.error' => static function (C $container): callable {
        return static function (string $message): void {
            WC_Admin_Settings::add_error($message);
        };
    },
    'zettle.onboarding.resettable.options' => static function (C $container): array {
        return [
            $container->get('zettle.onboarding.option.state'),
            $container->get('zettle.webhook.storage.option'),
            $container->get('zettle.sdk.option.integration'),
            $container->get('zettle.auth.is-failed.key'),
        ];
    },
    'zettle.onboarding.resettable.transients' => static function (C $container): array {
        return [
            $container->get('zettle.sdk.dal.provider.organization.transient-key'),
        ];
    },
    'zettle.onboarding.resettable.tables' => static function (C $container): array {
        $idMap = $container->get('zettle.sdk.dal.table');
        $queueTable = $container->get('inpsyde.queue.table');

        return [
            $idMap->name(),
            $queueTable->name(),
        ];
    },
    $job(ResetOnboardingJob::TYPE) => static function (C $container): Job {
        return new ResetOnboardingJob(
            $container->get('zettle.onboarding.wpdb'),
            $container->get('zettle.settings'),
            $container->get('zettle.setup-info'),
            $container->get('zettle.oauth.token-storage'),
            $container->get('zettle.onboarding.resettable.tables'),
            $container->get('zettle.onboarding.resettable.transients'),
            $container->get('zettle.onboarding.resettable.options'),
            $container->get('zettle.webhook.delete')
        );
    },
    'zettle.onboarding.cli.reset.onboarding' =>
        static function (C $container) use ($job): ResetOnboardingCommand {
            return new ResetOnboardingCommand(
                $container->get($job(ResetOnboardingJob::TYPE)),
                $container->get('zettle.is-multisite'),
                $container->get('zettle.current-site-id'),
                $container->get('inpsyde.queue.logger')
            );
        },
    'zettle.onboarding.provider.state-machine' => static function (C $container): Provider {
        return new StateMachineProvider(
            $container->get('inpsyde.state-machine')
        );
    },
    'zettle.onboarding.provider.cli.command.reset' => static function (C $container): Provider {
        return new ResetCommandProvider(
            $container->get('zettle.onboarding.cli.reset.onboarding')
        );
    },
    'zettle.onboarding.provider.listener.error' => static function (C $container): Provider {
        return new ErrorListenerProvider(
            $container->get('zettle.onboarding.failure.listener')
        );
    },
    'zettle.onboarding.provider.render' => static function (C $container): Provider {
        return new OnboardingRenderProvider(
            $container->get('inpsyde.state-machine')
        );
    },
    'zettle.onboarding.provider' => static function (C $container): array {
        return [
            $container->get('zettle.onboarding.provider.state-machine'),
            $container->get('zettle.onboarding.provider.listener.error'),
            $container->get('zettle.onboarding.provider.render'),
            $container->get('zettle.onboarding.provider.cli.command.reset'),
        ];
    },

    'zettle.onboarding.rest.namespace' => static function (): string {
        return "zettle-onboarding/v1";
    },
    'zettle.onboarding.disconnect.endpoint' => static function (C $container): EndpointInterface {
        return new DisconnectEndpoint(
            $container->get('zettle.job.' . ResetOnboardingJob::TYPE),
            $container->get('zettle.logger')
        );
    },
    'zettle.onboarding.disconnect.endpoint.url' => static function (C $container): string {
        $endpoint = $container->get('zettle.onboarding.disconnect.endpoint');
        return rest_url(
            $container->get('zettle.onboarding.rest.namespace') . $endpoint->route()
        );
    },

    'zettle.onboarding.first-import-timestamp' => static function (C $container): ?int {
        $setupInfo = $container->get('zettle.setup-info');
        if ($setupInfo->has('first_import_timestamp')) {
            return $setupInfo->get('first_import_timestamp');
        }

        return $container->get('zettle.plugin.properties')->lastUpdateTimestamp();
    },
];
