<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\ProductSettings;

use Inpsyde\Assets\Asset;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Inpsyde\Zettle\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Inpsyde\Zettle\ProductSettings\Components\TermManager;
use Psr\Container\ContainerInterface as C;

return [
    'zettle.sync.product.sync-active-for-id' =>
        static function (C $container, callable $previous): callable {
            return static function (int $productId) use ($container, $previous): bool {
                if (!$previous($productId)) {
                    return false;
                }

                $repository = $container->get('zettle.sdk.repository.woocommerce.product');
                assert($repository instanceof ProductRepositoryInterface);

                $product = $repository->findByIdOrVariationId($productId);
                if (!$product) {
                    return false;
                }

                $excludedFromSync = $container->get('zettle.product-settings.term.excluded');
                assert($excludedFromSync instanceof TermManager);

                return !$excludedFromSync->hasTerm((int) $product->get_id());
            };
        },
    'inpsyde.assets.registry' =>
        static function (C $container, array $previous): array {
            $assetUri = rtrim(plugins_url('/assets/', __DIR__ . '/zettle-pos-integration.php'), '/\\');

            $isProductsEditor = $container->get('zettle.product-settings.is-product-editor');

            // Products Editor Style
            $productEditorStyle = (new Style(
                'zettle-product-editor-style',
                "{$assetUri}/products-style.css",
                Asset::BACKEND
            ))
                ->canEnqueue($isProductsEditor());

            // Products Editor Script
            $productEditorScript = (new Script(
                'zettle-products-script',
                "{$assetUri}/products-editor.js",
                Asset::BACKEND
            ))
                ->canEnqueue($isProductsEditor())
                ->withLocalize(
                    'zettleBarcodeScanning',
                    [
                        'initErrorMessage' => __(
                            'Failed to start scanning. Please check your camera and try again.',
                            'zettle-pos-integration'
                        ),
                    ]
                );

            return array_merge(
                [$productEditorStyle, $productEditorScript],
                $previous
            );
        },
    'inpsyde.metabox.registry' => static function (C $container, array $previous): array {
        $previous[] = $container->get(
            'zettle.product-settings.metabox.product.library.link'
        );

        return $previous;
    },
];
