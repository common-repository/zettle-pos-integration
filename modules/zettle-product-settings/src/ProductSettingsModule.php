<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\ProductSettings;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Inpsyde\Zettle\BootableProviderAwareTrait;
use Inpsyde\Zettle\BootableProviderModuleInterface;
use Inpsyde\Zettle\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Inpsyde\Zettle\ProductSettings\Barcode\BarcodeInputField;
use Inpsyde\Zettle\ProductSettings\Barcode\VariantBarcodeSaveHandler;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as C;
use WP_Post;

class ProductSettingsModule implements ModuleInterface, BootableProviderModuleInterface
{
    use BootableProviderAwareTrait;

    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(
            require __DIR__ . '/../services.php',
            require __DIR__ . '/../extensions.php'
        );
    }

    /**
     * @inheritDoc
     */
    public function run(C $container): void
    {
        $this->bootProviders(
            $container,
            ...$container->get('zettle.product-settings.provider')
        );

        // do this a bit later to make the filter adding more flexible
        add_action('init', function () use ($container) {
            if ($container->get('zettle.product-settings.barcode.standard-ui-enabled')) {
                $this->addVariationBarcodeHandlers(
                    $container->get('zettle.product-settings.barcode.input-field.variation'),
                    $container->get('zettle.sdk.repository.woocommerce.product'),
                    $container->get('zettle.product-settings.barcode.save-handler.variation')
                );
            }
        });
    }

    private function addVariationBarcodeHandlers(
        BarcodeInputField $barcodeField,
        ProductRepositoryInterface $wcProductRepository,
        VariantBarcodeSaveHandler $saveHandler
    ) {
        add_action('woocommerce_product_after_variable_attributes', static function (
            int $loop,
            array $variationData,
            WP_Post $variationPost
        ) use (
            $barcodeField,
            $wcProductRepository
        ) {
            $variation = $wcProductRepository->findById((int) $variationPost->ID);
            if (!$variation) {
                return;
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $barcodeField->render($variation, $loop);
        }, 10, 3);

        add_action('woocommerce_save_product_variation', [$saveHandler, 'save'], 10, 2);
    }
}
