<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\ProductSettings\Provider;

use Inpsyde\Zettle\ProductSettings\Components\ProductSettingsTab;
use Inpsyde\Zettle\Provider;
use Psr\Container\ContainerInterface as C;

class CustomProductTabProvider implements Provider
{

    /**
     * @var ProductSettingsTab
     */
    private $settingsTab;

    /**
     * CustomProductTabProvider constructor.
     *
     * @param ProductSettingsTab $settingsTab
     */
    public function __construct(ProductSettingsTab $settingsTab)
    {
        $this->settingsTab = $settingsTab;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_filter(
            'woocommerce_product_data_tabs',
            [$this->settingsTab, 'addTab']
        );

        add_action(
            'admin_head',
            [$this->settingsTab, 'addCustomTabIcon']
        );

        // do this a bit later to make the filter adding more flexible
        add_action('init', function () use ($container) {
            $addBarcodeInput = $container->get('zettle.product-settings.barcode.standard-ui-enabled');

            add_action(
                'woocommerce_product_data_panels',
                function () use ($addBarcodeInput) {
                    $this->settingsTab->renderSettings($addBarcodeInput);
                }
            );
        });

        add_action(
            'woocommerce_process_product_meta',
            [$this->settingsTab, 'saveFields']
        );

        return true;
    }
}
