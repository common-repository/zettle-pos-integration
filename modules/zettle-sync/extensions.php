<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Sync;

use Inpsyde\Zettle\Onboarding\SyncCollisionStrategy;
use Inpsyde\Zettle\PhpSdk\Filter\PriceFilter;
use Inpsyde\Zettle\PhpSdk\Filter\VatFilter;
use Inpsyde\WcEvents\Event\ProductEventListenerRegistry;
use Psr\Container\ContainerInterface;

return [
    'zettle.settings.fields.registry' =>
        static function (ContainerInterface $container, array $previous): array {
            return array_merge(
                $previous,
                [
                    'sync_params' => [
                        'title' => __('Sync Parameters', 'zettle-pos-integration'),
                        'type' => 'title',
                        'description' => __(
                            'Sets up how and what to synchronize to your PayPal Zettle store',
                            'zettle-pos-integration'
                        ),
                    ],
                    'sync_price_strategy' => [
                        'title' => __('Price synchronization', 'zettle-pos-integration'),
                        'type' => 'select',
                        'default' => PriceSyncMode::ENABLED,
                        'description' => __(
                            'Whether or not to sync prices to PayPal Zettle',
                            'zettle-pos-integration'
                        ),
                        'options' => [
                            PriceSyncMode::ENABLED => __('Sync prices', 'zettle-pos-integration'),
                            PriceSyncMode::DISABLED => __('Don\'t sync prices', 'zettle-pos-integration'),
                        ],
                    ],
                    'sync_collision_strategy' => [
                        'title' => __('Existing products', 'zettle-pos-integration'),
                        'type' => 'select',
                        'default' => SyncCollisionStrategy::MERGE,
                        'description' => __(
                            'Replace existing products or add WooCommerce products to existing ones',
                            'zettle-pos-integration'
                        ),
                        // The first Option will be used as default
                        'options' => [
                            SyncCollisionStrategy::MERGE => __(
                                'Add WooCommerce products',
                                'zettle-pos-integration'
                            ),
                            SyncCollisionStrategy::WIPE => __(
                                'Replace PayPal Zettle library',
                                'zettle-woocommerce'
                            ),
                        ],
                    ],
                ]
            );
        },
    'zettle.sdk.filters' =>
        static function (ContainerInterface $container, array $previous): array {
            $settings = $container->get('zettle.settings');
            if (!$settings->has('sync_price_strategy')) {
                return $previous;
            }

            $priceSyncMode = $settings->get('sync_price_strategy');

            if ($priceSyncMode === PriceSyncMode::DISABLED) {
                $previous[] = new PriceFilter();
            }

            return $previous;
        },
    'inpsyde.wc-lifecycle-events.products.listener-provider' =>
        static function (
            ContainerInterface $container,
            ProductEventListenerRegistry $registry
        ): ProductEventListenerRegistry {
            $registry->onPropertyChange(
                'stock_quantity',
                $container->get('zettle.sync.listener.stock-quantity')
            );

            $registry->onPropertyChange(
                'manage_stock',
                $container->get('zettle.sync.listener.manage-stock.simple'),
                $container->get('zettle.sync.listener.manage-stock.variable'),
                $container->get('zettle.sync.listener.manage-stock.variation')
            );

            $registry->onChange(
                $container->get('zettle.sync.listener.all-props'),
                $container->get('zettle.sync.listener.not-syncable'),
                $container->get('zettle.sync.listener.variation.parent-stock'),
                $container->get('zettle.sync.listener.delete-variable-without-variation')
            );

            $registry->onTypeChange(
                $container->get('zettle.sync.listener.type-change.simple-to-variable'),
                $container->get('zettle.sync.listener.type-change.variable-to-simple')
            );

            $registry->onDelete(
                $container->get('zettle.sync.listener.delete.variation'),
                $container->get('zettle.sync.listener.depublish')
            );

            $registry->onDraft(
                $container->get('zettle.sync.listener.depublish')
            );

            $registry->onTrash(
                $container->get('zettle.sync.listener.depublish')
            );

            $registry->onPending(
                $container->get('zettle.sync.listener.depublish')
            );

            $registry->onPrivate(
                $container->get('zettle.sync.listener.depublish')
            );

            $registry->onHide(
                $container->get('zettle.sync.listener.depublish')
            );

            $registry->onPublish(
                $container->get('zettle.sync.listener.publish.variation')
            );

            return $registry;
        },
];
