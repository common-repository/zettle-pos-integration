<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Sync;

use Exception;
use Inpsyde\Queue\Processor\ProcessorBuilder;
use Inpsyde\Queue\Processor\QueueProcessor;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Organization\TaxationType;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Tax\TaxRate;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Organization\OrganizationProvider;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Validator\VariantOptionDefinitionsValidator;
use Inpsyde\Zettle\PhpSdk\Validator\VariantOptionValidator;
use Inpsyde\Zettle\Sync\Cli\ExcludeCommand;
use Inpsyde\Zettle\Sync\Cli\ExportCommand;
use Inpsyde\Zettle\Sync\Cli\ResetCommand;
use Inpsyde\Zettle\Sync\Cli\SyncCommand;
use Inpsyde\Zettle\Sync\Cli\UnlinkCommand;
use Inpsyde\Zettle\Sync\Job\DeleteProductJob;
use Inpsyde\Zettle\Sync\Job\EnqueueProductSyncJob;
use Inpsyde\Zettle\Sync\Job\ExportProductJob;
use Inpsyde\Zettle\Sync\Job\ReExportProductJob;
use Inpsyde\Zettle\Sync\Job\SetInventoryTrackingJob;
use Inpsyde\Zettle\Sync\Job\SetStateJob;
use Inpsyde\Zettle\Sync\Job\SyncStockJob;
use Inpsyde\Zettle\Sync\Job\UnlinkImages;
use Inpsyde\Zettle\Sync\Job\UnlinkProductJob;
use Inpsyde\Zettle\Sync\Job\UnlinkVariantJob;
use Inpsyde\Zettle\Sync\Job\WipeRemoteProductsJob;
use Inpsyde\Zettle\Sync\Listener\AllPropsListener;
use Inpsyde\Zettle\Sync\Listener\DeleteVariableWithoutVariationsListener;
use Inpsyde\Zettle\Sync\Listener\DePublishListener;
use Inpsyde\Zettle\Sync\Listener\NotSyncableListener;
use Inpsyde\Zettle\Sync\Listener\ParentStockVariationListener;
use Inpsyde\Zettle\Sync\Listener\SimpleManageStockListener;
use Inpsyde\Zettle\Sync\Listener\SimpleToVariableTypeChangeListener;
use Inpsyde\Zettle\Sync\Listener\StockQuantityListener;
use Inpsyde\Zettle\Sync\Listener\StockSyncOnVariationPublishListener;
use Inpsyde\Zettle\Sync\Listener\VariableManageStockListener;
use Inpsyde\Zettle\Sync\Listener\VariableToSimpleTypeChangeListener;
use Inpsyde\Zettle\Sync\Listener\VariationDeleteListener;
use Inpsyde\Zettle\Sync\Listener\VariationManageStockListener;
use Inpsyde\Zettle\Sync\Status\StatusCodeMatcher;
use Inpsyde\Zettle\Sync\Status\SyncStatusCodes;
use Inpsyde\Zettle\Sync\Validator\ProductValidator;
use Psr\Container\ContainerInterface as C;
use Throwable;
use WC_Product_Variation;

$job = static function (string $type): string {
    return "zettle.job.{$type}";
};

return [
    'zettle.sync.status.map' => static function (C $container): array {
        return [
            SyncStatusCodes::NO_VALID_PRODUCT_ID => __('Invalid product ID', 'zettle-pos-integration'),
            SyncStatusCodes::PRODUCT_NOT_FOUND => __('Product not found', 'zettle-pos-integration'),

            SyncStatusCodes::SYNCED => __('Synced', 'zettle-pos-integration'),
            SyncStatusCodes::NOT_SYNCED => __('Not synced', 'zettle-pos-integration'),
            SyncStatusCodes::SYNCABLE => __('Syncable', 'zettle-pos-integration'),
            SyncStatusCodes::NOT_SYNCABLE => __('Not syncable', 'zettle-pos-integration'),

            SyncStatusCodes::UNSUPPORTED_PRODUCT_TYPE => __('Unsupported product type', 'zettle-pos-integration'),
            SyncStatusCodes::EXCLUDED => __('Excluded', 'zettle-pos-integration'),
            SyncStatusCodes::UNPUBLISHED => __('Not published', 'zettle-pos-integration'),
            SyncStatusCodes::UNPURCHASABLE => __('Not purchasable', 'zettle-pos-integration'),
            SyncStatusCodes::INVISIBLE => __('Not visible', 'zettle-pos-integration'),

            ValidationErrorCodes::NO_VARIANTS => __('No variations', 'zettle-pos-integration'),
            ValidationErrorCodes::TOO_MANY_VARIANTS => __('Too many variations', 'zettle-pos-integration'),
            ValidationErrorCodes::NO_VARIANT_OPTIONS => __('No variation attributes', 'zettle-pos-integration'),
            ValidationErrorCodes::TOO_MANY_VARIANT_OPTIONS => sprintf(
                /* translators: %1$d max attributes amount for variation */
                __(
                    'Too many variation attributes, more than %1$d',
                    'zettle-pos-integration'
                ),
                VariantOptionDefinitionsValidator::MAXIMUM_DEFINITIONS_AMOUNT
            ),
            ValidationErrorCodes::TOO_SHORT_VARIANT_NAME => __('Empty variation attribute value', 'zettle-pos-integration'),
            ValidationErrorCodes::TOO_LONG_VARIANT_NAME => sprintf(
            /* translators: %1$d max variation attribute value length (e.g. 30) */
                __(
                    'Too long variation attribute value, more than %1$d letters',
                    'zettle-pos-integration'
                ),
                VariantOptionValidator::MAX_NAME_LENGTH
            ),
            ValidationErrorCodes::DIFFERING_VARIANT_TAXES => __('Variations have differing taxes', 'zettle-pos-integration'),

            ValidationErrorCodes::TOO_BIG_STOCK => sprintf(
            /* translators: %1$d max stock quantity allowed in Zettle (e.g. 99999) */
                __(
                    'Stock cannot be greater than %1$d',
                    'zettle-pos-integration'
                ),
                $container->get('zettle.sdk.validator.stock.max')
            ),

            ValidationErrorCodes::TAX_RATE_NOT_FOUND => __('No tax rate', 'zettle-pos-integration'),

            SyncStatusCodes::UNDEFINED => __('Undefined status code, check WC logs', 'zettle-pos-integration'),
        ];
    },
    $job(EnqueueProductSyncJob::TYPE) => static function (C $container): Job {
        $createJob = $container->get('inpsyde.queue.create-job-record');
        assert(is_callable($createJob));

        return new EnqueueProductSyncJob(
            $container->get('zettle.sync.allowed-product-types'),
            $createJob,
            $container->get('zettle.sync.product.sync-active-for-id')
        );
    },
    $job(ExportProductJob::TYPE) => static function (C $container): Job {
        return new ExportProductJob(
            $container->get('zettle.sdk.repository.woocommerce.product'),
            $container->get('zettle.sdk.builder'),
            $container->get('zettle.sdk.api.products'),
            $container->get('zettle.sdk.id-map.product'),
            $container->get('zettle.sync.queue-processor.job.factory')(),
            $container->get('inpsyde.queue.create-job-record'),
            $container->get('zettle.sync.product.sync-active-for-id'),
            $container->get('zettle.sync.product.status')
        );
    },
    $job(DeleteProductJob::TYPE) => static function (C $container): Job {
        return new DeleteProductJob(
            $container->get('zettle.sdk.id-map.product'),
            $container->get('zettle.sdk.api.products'),
            $container->get('inpsyde.queue.create-job-record')
        );
    },
    $job(WipeRemoteProductsJob::TYPE) => static function (C $container): Job {
        return new WipeRemoteProductsJob(
            $container->get('zettle.sdk.api.products')
        );
    },
    $job(UnlinkProductJob::TYPE) => static function (C $container): Job {
        return new UnlinkProductJob(
            $container->get('zettle.sdk.id-map.product'),
            $container->get('zettle.sdk.id-map.variant'),
            $container->get('zettle.sdk.repository.woocommerce.product')
        );
    },
    $job(UnlinkVariantJob::TYPE) => static function (C $container): Job {
        return new UnlinkVariantJob(
            $container->get('zettle.sdk.id-map.variant')
        );
    },
    $job(SetStateJob::TYPE) => static function (C $container): Job {
        $setState = $container->get('zettle.onboarding.set-state');
        assert(is_callable($setState));

        return new SetStateJob($setState);
    },
    $job(SyncStockJob::TYPE) => static function (C $container) use ($job): Job {
        return new SyncStockJob(
            $container->get('zettle.sdk.api.inventory'),
            $container->get('zettle.sdk.builder'),
            $container->get('zettle.sdk.id-map.variant'),
            $container->get('zettle.sync.queue-processor.job.factory')(),
            $container->get('zettle.sdk.validator.stock.max'),
            $container->get($job(SetInventoryTrackingJob::TYPE))
        );
    },
    $job(SetInventoryTrackingJob::TYPE) => static function (C $container): Job {
        return new SetInventoryTrackingJob(
            $container->get('zettle.sdk.repository.woocommerce.product'),
            $container->get('zettle.sdk.api.inventory'),
            $container->get('zettle.sdk.builder'),
            $container->get('zettle.sdk.id-map.variant')
        );
    },
    $job(UnlinkImages::TYPE) => static function (C $container): Job {
        return new UnlinkImages(
            $container->get('zettle.sdk.id-map.image'),
            $container->get('zettle.sdk.repository.woocommerce.product')
        );
    },
    $job(ReExportProductJob::TYPE) => static function (C $container): Job {
        return new ReExportProductJob(
            $container->get('zettle.sdk.repository.zettle.product'),
            $container->get('zettle.sdk.repository.woocommerce.product'),
            $container->get('zettle.sdk.id-map.variant'),
            $container->get('inpsyde.queue.create-job-record')
        );
    },
    /**
     * Configure 2 separate Queue processors.
     * It is important that these are separate instances, even if they have the same configuration
     */
    'zettle.sync.queue-processor.cli' =>
        static function (C $container) use ($job): QueueProcessor {
            $processorBuilder = new ProcessorBuilder(
                $container->get('inpsyde.queue.factory')
            );

            return $processorBuilder
                ->withLogger($container->get('inpsyde.queue.logger'))
                ->withExceptionHandler($container->get('zettle.sync.queue-processor.cli.exception-handler'))
                ->withMaxRetriesCount($container->get('inpsyde.queue.failed.retry.count'))
                ->build();
        },
    'zettle.sync.queue-processor.job.factory' =>
        static function (C $container) use ($job): callable {
            return static function () use ($container): QueueProcessor {
                $processorBuilder = new ProcessorBuilder(
                    $container->get('inpsyde.queue.factory')
                );

                return $processorBuilder
                    ->withLogger($container->get('inpsyde.queue.logger'))
                    ->withMaxRetriesCount($container->get('inpsyde.queue.failed.retry.count'))
                    ->build();
            };
        },
    'zettle.sync.queue-processor.cli.exception-handler' => static function (): callable {
        return static function (Throwable $exception) {
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo $exception;
        };
    },
    'zettle.sync.cli.sync-product' => static function (C $container) use ($job): SyncCommand {
        return new SyncCommand(
            $container->get('zettle.sync.queue-processor.cli'),
            $container->get('inpsyde.queue.create-job-record')
        );
    },
    'zettle.sync.cli.unlink-product' => static function (C $container) use ($job): UnlinkCommand {
        return new UnlinkCommand(
            $container->get($job(UnlinkProductJob::TYPE)),
            $container->get($job(UnlinkVariantJob::TYPE)),
            $container->get($job(UnlinkImages::TYPE)),
            $container->get('inpsyde.queue.logger')
        );
    },
    'zettle.sync.cli.export' => static function (C $container) use ($job): ExportCommand {
        return new ExportCommand(
            $container->get('zettle.sync.queue-processor.cli'),
            $container->get('inpsyde.queue.create-job-record')
        );
    },
    'zettle.sync.cli.reset' => static function (C $container) use ($job): ResetCommand {
        return new ResetCommand(
            $container->get($job(WipeRemoteProductsJob::TYPE)),
            $container->get('inpsyde.queue.logger'),
            $container->get('zettle.sdk.dal.table'),
            $container->get('inpsyde.queue.table')
        );
    },
    'zettle.sync.cli.exclude' => static function (C $container) use ($job): ExcludeCommand {
        return new ExcludeCommand(
            $container->get($job(DeleteProductJob::TYPE)),
            $container->get($job(UnlinkProductJob::TYPE)),
            $container->get('inpsyde.queue.logger')
        );
    },
    'zettle.sync.enqueue-initial-sync' => static function (C $container): callable {
        return static function () use ($container) {
            $enqueue = $container->get('inpsyde.queue.enqueue-job');
            assert(is_callable($enqueue));
            $enqueue(EnqueueProductSyncJob::TYPE);
        };
    },
    'zettle.sync.allowed-product-types' => static function (C $container): array {
        return [
            'simple',
            'variable',
        ];
    },
    'zettle.sync.price-sync-enabled' => static function (C $container): bool {
        $settings = $container->get('zettle.settings');
        assert($settings instanceof C);

        return $settings->has('sync_price_strategy')
            && $settings->get('sync_price_strategy') === PriceSyncMode::ENABLED;
    },
    'zettle.sync.validator.product' =>
        static function (C $container): ProductValidator {
            return new ProductValidator(
                $container->get('zettle.sdk.id-map.product'),
                $container->get('zettle.product-settings.term.excluded'),
                (array) $container->get('zettle.sync.allowed-product-types'),
                $container->get('zettle.init-possible'),
                $container->get('zettle.sdk.builder'),
                $container->get('inpsyde.debug.exception-handler')
            );
        },
    'zettle.sync.status.matcher' =>
        static function (C $container): StatusCodeMatcher {
            return new StatusCodeMatcher(
                $container->get('zettle.sync.status.map')
            );
        },
    'zettle.sync.product.sync-active-for-id' => static function (C $container): callable {
        return static function (int $productId) use ($container): bool {
            $productValidator = $container->get('zettle.sync.validator.product');
            $isValid = $productValidator->validate($productId);
            /**
             * Recurse into the parent product if we have a Variation at our hands
             */
            $product = $product = wc_get_product($productId);
            if ($product instanceof WC_Product_Variation) {
                // Grab the "full" service again so we gather all potential extensions with it
                $self = $container->get('zettle.sync.product.sync-active-for-id');
                return $self($product->get_parent_id());
            }

            return empty($isValid);
        };
    },
    'zettle.sync.product.status' => static function (C $container): callable {
        return static function (int $productId) use ($container): array {
            $productValidator = $container->get('zettle.sync.validator.product');
            $productStatusMatcher = $container->get('zettle.sync.status.matcher');

            return $productStatusMatcher->match(
                $productValidator->validate($productId)
            );
        };
    },
    'zettle.sync.editor.action' => static function (): callable {
        return static function (): bool {
            /**
             * We're saving a product via editor.
             * Any changes here should have been in $updatedProperties
             *
             * true if a post was updated via editor
             */
            return doing_action('woocommerce_process_product_meta');
        };
    },
    /**
     * WooCommerce Lifecycle Event Listeners
     */
    'zettle.sync.listener.depublish' => static function (C $container): DePublishListener {
        return new DePublishListener(
            $container->get('zettle.sdk.id-map.product'),
            $container->get('inpsyde.queue.enqueue-job')
        );
    },
    'zettle.sync.listener.publish.variation' =>
        static function (C $container): StockSyncOnVariationPublishListener {
            return new StockSyncOnVariationPublishListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('zettle.sync.product.sync-active-for-id')
            );
        },
    'zettle.sync.listener.delete.variation' =>
        static function (C $container): VariationDeleteListener {
            return new VariationDeleteListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('zettle.sync.product.sync-active-for-id')
            );
        },
    'zettle.sync.listener.type-change.simple-to-variable' =>
        static function (C $container): VariableToSimpleTypeChangeListener {
            return new VariableToSimpleTypeChangeListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('zettle.sync.product.sync-active-for-id')
            );
        },
    'zettle.sync.listener.type-change.variable-to-simple' =>
        static function (C $container): SimpleToVariableTypeChangeListener {
            return new SimpleToVariableTypeChangeListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('zettle.sync.product.sync-active-for-id')
            );
        },
    'zettle.sync.listener.all-props' => static function (C $container): AllPropsListener {
        return new AllPropsListener(
            $container->get('inpsyde.queue.enqueue-job'),
            $container->get('zettle.sync.product.sync-active-for-id')
        );
    },
    'zettle.sync.listener.not-syncable' =>
        static function (C $container): NotSyncableListener {
            return new NotSyncableListener(
                $container->get('zettle.sync.listener.depublish'),
                $container->get('zettle.sync.product.sync-active-for-id')
            );
        },
    'zettle.sync.listener.variation.parent-stock' =>
        static function (C $container): ParentStockVariationListener {
            return new ParentStockVariationListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('zettle.sync.product.sync-active-for-id')
            );
        },
    'zettle.sync.listener.stock-quantity' =>
        static function (C $container): StockQuantityListener {
            return new StockQuantityListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('zettle.sync.product.sync-active-for-id')
            );
        },
    'zettle.sync.listener.manage-stock.simple' =>
        static function (C $container): SimpleManageStockListener {
            return new SimpleManageStockListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('zettle.sync.product.sync-active-for-id')
            );
        },
    'zettle.sync.listener.manage-stock.variable' =>
        static function (C $container): VariableManageStockListener {
            return new VariableManageStockListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('zettle.sync.product.sync-active-for-id')
            );
        },
    'zettle.sync.listener.manage-stock.variation' =>
        static function (C $container): VariationManageStockListener {
            return new VariationManageStockListener(
                $container->get('zettle.sync.listener.manage-stock.variable')
            );
        },
    'zettle.sync.listener.delete-variable-without-variation' =>
        static function (C $container): DeleteVariableWithoutVariationsListener {
            return new DeleteVariableWithoutVariationsListener(
                $container->get('zettle.sdk.id-map.product'),
                $container->get('zettle.sdk.id-map.variant'),
                $container->get('zettle.sdk.api.products'),
                $container->get('inpsyde.queue.logger')
            );
        },

    'zettle.sync.taxation-mode' =>
        static function (C $container): string {
            $orgProvider = $container->get('zettle.sdk.dal.provider.organization');
            assert($orgProvider instanceof OrganizationProvider);

            $org = $orgProvider->provide();

            return $org->taxationMode();
        },

    'zettle.sync.taxation-type' =>
        static function (C $container): string {
            $orgProvider = $container->get('zettle.sdk.dal.provider.organization');
            assert($orgProvider instanceof OrganizationProvider);

            $org = $orgProvider->provide();

            return $org->taxationType();
        },
    'zettle.sdk.default-taxes' => static function (C $container): ?array {
        if ($container->get('zettle.sync.taxation-type') !== TaxationType::SALES_TAX) {
            return null;
        }

        try {
            $api = $container->get('zettle.sdk.api.taxes');
            $taxRates = $api->all();

            return array_filter($taxRates, static function (TaxRate $rate): bool {
                return $rate->isDefault();
            });
        } catch (Exception $exception) {
            $container->get('inpsyde.queue.logger')
                ->warning(sprintf('Failed to get PayPal Zettle taxes: %1$s', $exception->getMessage()));
            return null;
        }
    },
];
