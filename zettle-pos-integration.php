<?php

//phpcs:disable PSR12.Files.FileHeader.IncorrectOrder
declare(strict_types=1);

/**
 * Plugin Name: PayPal Zettle POS
 * Plugin URI:  https://zettle.inpsyde.com/
 * Description: PayPal Zettle Point-Of-Sale Integration for WooCommerce
 * Version:     1.5.9
 * Requires at least: 5.4
 * Requires PHP: 7.2
 * Requires Plugins: woocommerce
 * WC requires at least: 4.3
 * WC tested up to: 9.1
 * Author:      PayPal
 * Author URI:  https://www.paypal.com/us/business/pos
 * License:     GPL-2.0
 * Text Domain: zettle-pos-integration
 * Domain Path: /languages
 */

/**
 * phpcs:disable PSR1.Files.SideEffects
 * phpcs:disable Squiz.PHP.CommentedOutCode.Found
 */

namespace Inpsyde\Zettle;

use Dhii\Validation\Exception\ValidationFailedExceptionInterface;
use Psr\Container\ContainerInterface;

(static function () {
    /**
     * Display an error message in the WP admin
     *
     * @param string $message The message content
     *
     * @return void
     */
    function errorNotice(string $message)
    {
        add_action(
            'all_admin_notices',
            static function () use ($message) {
                $class = 'notice notice-error';
                printf(
                    '<div class="%1$s"><p>%2$s</p></div>',
                    esc_attr($class),
                    wp_kses_post($message)
                );
            }
        );
    }

    $requiresAtLeast = '7.2';
    if (version_compare(PHP_VERSION, $requiresAtLeast, '<')) {
        errorNotice(
            sprintf(
            /* translators: required PHP version */
                esc_html__(
                    'PayPal Zettle POS requires at least PHP version %s.',
                    'zettle-pos-integration'
                ),
                $requiresAtLeast
            )
            . '<br>' .
            sprintf(
            /* translators: required PHP version */
                esc_html__(
                    'Please ask your server administrator to update your environment to PHP version %s.',
                    'zettle-pos-integration'
                ),
                $requiresAtLeast
            )
        );
        return;
    }

    if (
        !class_exists(PluginModule::class)
        && file_exists(__DIR__ . '/vendor/autoload.php')
    ) {
        include_once __DIR__ . '/vendor/autoload.php';
    }

    function init(): ?ContainerInterface
    {
        static $initialized;
        static $container;
        if (!$initialized) {
            try {
                $container = (require __DIR__ . '/bootstrap.php')(__DIR__, true);
            } catch (ValidationFailedExceptionInterface $exc) {
                $messages = array_map(static function ($error): string {
                    if ($error instanceof ValidationFailedExceptionInterface) {
                        return $error->getMessage();
                    }
                    return (string) $error;
                }, $exc->getValidationErrors());

                foreach ($messages as $message) {
                    errorNotice($message);
                }

                return null;
            }

            $initialized = true;
        }

        return $container;
    }

    add_action(
        'plugins_loaded',
        static function () {
            $container = init();

            if (!$container) {
                return;
            }

            // IZET-356, looks like there is no good built-in hook in WP for plugin upgrades
            $version = $container->get('zettle.plugin.properties')->version();
            $versionOptionName = $container->get('zettle.version-option-key');
            if (get_option($versionOptionName) !== $version) {
                do_action('zettle-pos-integration.migrate');

                update_option($versionOptionName, $version);
            }
        }
    );
    register_activation_hook(
        __FILE__,
        static function () {
            init();
            do_action('zettle-pos-integration.activate');
        }
    );
    register_deactivation_hook(
        __FILE__,
        static function () {
            init();
            do_action('zettle-pos-integration.deactivate');
        }
    );

    add_action(
        'before_woocommerce_init',
        static function () {
            if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        }
    );
})();
