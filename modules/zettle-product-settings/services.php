<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\ProductSettings;

use Inpsyde\Zettle\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Inpsyde\Zettle\ProductSettings\Barcode\BarcodeInputField;
use Inpsyde\Zettle\ProductSettings\Barcode\Repository\BarcodeRepository;
use Inpsyde\Zettle\ProductSettings\Barcode\VariantBarcodeSaveHandler;
use Inpsyde\Zettle\ProductSettings\Components\ProductSettingsTab;
use Inpsyde\Zettle\ProductSettings\Components\TermManager;
use Inpsyde\Zettle\ProductSettings\Handler\ProductExcludeHandler;
use Inpsyde\Zettle\ProductSettings\Metabox\ReadonlyMetaboxAction;
use Inpsyde\Zettle\ProductSettings\Metabox\ZettleProductLibraryLink;
use Inpsyde\Zettle\ProductSettings\Metabox\ZettleProductLibraryLinkView;
use Inpsyde\Zettle\ProductSettings\Provider\CustomProductTabProvider;
use Inpsyde\Zettle\ProductSettings\Provider\ProductExcludeProvider;
use Inpsyde\Zettle\ProductSettings\Provider\SyncVisibilityTaxonomyProvider;
use Inpsyde\Zettle\ProductSettings\Taxonomy\ZettleSyncVisibilityTaxonomy;
use Inpsyde\Zettle\Provider;
use MetaboxOrchestra\BoxAction;
use MetaboxOrchestra\BoxView;
use Psr\Container\ContainerInterface as C;

return [
    'zettle.product-settings.taxonomy.sync-visibility.key' =>
        static function (C $container): string {
            return 'zettle_sync_visibility';
        },
    'zettle.product-settings.taxonomy.sync-visibility.post-type' =>
        static function (C $container): string {
            return 'product';
        },
    'zettle.product-settings.taxonomy.sync-visibility' =>
        static function (C $container): ZettleSyncVisibilityTaxonomy {
            return new ZettleSyncVisibilityTaxonomy(
                $container->get('zettle.product-settings.taxonomy.sync-visibility.key'),
                $container->get('zettle.product-settings.taxonomy.sync-visibility.post-type')
            );
        },
    'zettle.product-settings.term.excluded.name' => static function (): string {
        return __('PayPal Zettle Excluded', 'zettle-pos-integration');
    },
    'zettle.product-settings.term.excluded.slug' => static function (): string {
        return __('zettle-excluded', 'zettle-pos-integration');
    },
    'zettle.product-settings.term.excluded' => static function (C $container): TermManager {
        $syncVisibilityTaxonomy = $container->get('zettle.product-settings.taxonomy.sync-visibility');
        assert($syncVisibilityTaxonomy instanceof ZettleSyncVisibilityTaxonomy);

        return new TermManager(
            $container->get('zettle.product-settings.term.excluded.name'),
            $container->get('zettle.product-settings.term.excluded.slug'),
            $syncVisibilityTaxonomy->key()
        );
    },
    'zettle.product-settings.product-settings-tab' =>
        static function (C $container): ProductSettingsTab {
            return new ProductSettingsTab(
                $container->get('zettle.logger'),
                $container->get('zettle.product-settings.term.excluded'),
                $container->get('zettle.sdk.repository.woocommerce.product'),
                $container->get('zettle.product-settings.barcode.input-field.simple'),
                $container->get('zettle.product-settings.barcode.repository')
            );
        },
    'zettle.product-settings.handler.exclude' =>
        static function (C $container): ProductExcludeHandler {
            return new ProductExcludeHandler(
                $container->get('zettle.sdk.repository.woocommerce.product'),
                $container->get('zettle.product-settings.term.excluded'),
                $container->get('inpsyde.queue.repository'),
                $container->get('inpsyde.queue.create-job-record')
            );
        },
    'zettle.product-settings.zettle.product.base-link' => static function (C $container): string {
        return esc_url_raw('https://my.zettle.com/products');
    },
    'zettle.product-settings.zettle.product.title' => static function (C $container): string {
        return esc_html__('PayPal Zettle Product Library Link', 'zettle-pos-integration');
    },
    'zettle.product-settings.metabox.product.library.link.view' =>
        static function (C $container): BoxView {
            return new ZettleProductLibraryLinkView(
                $container->get('zettle.product-settings.zettle.product.base-link')
            );
        },
    'zettle.product-settings.metabox.product.library.link.action' =>
        static function (): BoxAction {
            return new ReadonlyMetaboxAction();
        },
    'zettle.product-settings.metabox.product.library.link' =>
        static function (C $container): ZettleProductLibraryLink {
            return new ZettleProductLibraryLink(
                $container->get('zettle.sdk.repository.zettle.product'),
                $container->get('zettle.product-settings.metabox.product.library.link.view'),
                $container->get('zettle.product-settings.metabox.product.library.link.action'),
                $container->get('zettle.product-settings.zettle.product.title')
            );
        },
    'zettle.product-settings.provider.sync-visibility' =>
        static function (C $container): Provider {
            return new SyncVisibilityTaxonomyProvider(
                $container->get('zettle.product-settings.taxonomy.sync-visibility')
            );
        },
    'zettle.product-settings.provider.product-settings-tab' =>
        static function (C $container): Provider {
            return new CustomProductTabProvider(
                $container->get('zettle.product-settings.product-settings-tab')
            );
        },
    'zettle.product-settings.provider.product-exclude-handler' =>
        static function (C $container): Provider {
            return new ProductExcludeProvider(
                $container->get('zettle.product-settings.handler.exclude')
            );
        },
    'zettle.product-settings.provider' => static function (C $container): array {
        return [
            $container->get('zettle.product-settings.provider.sync-visibility'),
            $container->get('zettle.product-settings.provider.product-settings-tab'),
            $container->get('zettle.product-settings.provider.product-exclude-handler'),
        ];
    },
    'zettle.product-settings.is-product-editor' => static function (C $container): callable {
        return static function () use ($container): bool {
            if (!isset($_SERVER['SCRIPT_FILENAME'])) {
                return false;
            }

            // using only to compare value, not storing anywhere.
            // phpcs:ignore WordPress.Security, using only to compare value, not storing anywhere.
            $currentView = $_SERVER['SCRIPT_FILENAME'] ?? '';
            if (!$currentView) {
                return false;
            }
            $currentView = basename($currentView, '.php');

            if ($currentView === 'post-new') {
                // see https://bugs.php.net/bug.php?id=49184
                // phpcs:ignore WordPress.Security, using only to compare value, not storing anywhere.
                $type = $_GET['post_type'] ?? '';

                return $type === 'product';
            } elseif ($currentView === 'post') {
                // phpcs:ignore WordPress.Security, using only to compare value, not storing anywhere.
                $action = wp_unslash($_GET['action'] ?? '');

                if ($action !== 'edit') {
                    return false;
                }

                return $container->get('zettle.product-settings.product.is-product')(
                    $container->get('zettle.product-settings.product-editor.product-from-url')()
                );
            }

            return false;
        };
    },
    'zettle.product-settings.product-editor.product-from-url' =>
        static function (C $container): callable {
            return static function (int $method = INPUT_GET): int {
                return (int) filter_input($method, 'post', FILTER_VALIDATE_INT);
            };
        },
    'zettle.product-settings.product.is-product' => static function (C $container): callable {
        return static function (int $productId) use ($container): bool {
            $repository = $container->get('zettle.sdk.repository.woocommerce.product');
            assert($repository instanceof ProductRepositoryInterface);

            $product = $repository->findById($productId);

            if ($product === null) {
                return false;
            }

            return true;
        };
    },

    'zettle.product-settings.barcode.input-field.name' => static function (): string {
        return '_zettle_barcode';
    },
    'zettle.product-settings.barcode.meta-key' => static function (): string {
        return '_zettle_barcode';
    },
    'zettle.product-settings.barcode.input-field.simple' => static function (
        C $container
    ): BarcodeInputField {
        return new BarcodeInputField(
            $container->get('zettle.product-settings.barcode.input-field.name'),
            $container->get('zettle.product-settings.barcode.repository'),
            __('Product barcode', 'zettle-pos-integration'),
            'zettle-simple-product-barcode'
        );
    },
    'zettle.product-settings.barcode.input-field.variation' => static function (
        C $container
    ): BarcodeInputField {
        return new BarcodeInputField(
            $container->get('zettle.product-settings.barcode.input-field.name'),
            $container->get('zettle.product-settings.barcode.repository'),
            __('Barcode', 'zettle-pos-integration'),
            'zettle-variation-product-barcode form-field form-row'
        );
    },
    'zettle.product-settings.barcode.repository' => static function (
        C $container
    ): BarcodeRepository {
        return new BarcodeRepository(
            $container->get('zettle.product-settings.barcode.meta-key'),
            'zettle-pos-integration.barcode.value'
        );
    },
    'zettle.product-settings.barcode.save-handler.variation' => static function (
        C $container
    ): VariantBarcodeSaveHandler {
        return new VariantBarcodeSaveHandler(
            $container->get('zettle.product-settings.barcode.repository'),
            $container->get('zettle.product-settings.barcode.input-field.variation'),
            $container->get('zettle.sdk.repository.woocommerce.product'),
            $container->get('zettle.logger')
        );
    },
    'zettle.product-settings.barcode.standard-ui-enabled' => static function (): bool {
        return apply_filters('zettle-pos-integration.barcode.standard-input-ui-enabled', true);
    },
];
