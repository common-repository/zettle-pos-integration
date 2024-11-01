<?php

declare(strict_types=1);

namespace Inpsyde\Zettle;

use Dhii\Collection\MutableContainerInterface;
use Dhii\Validation\ValidatorInterface;
use Dhii\Validator\CompositeValidator;
use Inpsyde\Zettle\Container\WpOptionContainer;
use Inpsyde\Zettle\Http\PageReloader;
use Inpsyde\Zettle\Http\PageReloaderInterface;
use Inpsyde\Zettle\Logging\Logger\CompoundLogger;
use Inpsyde\Zettle\Validation\RequiredExtensionsValidator;
use Inpsyde\Zettle\Validation\RequiredPluginsValidator;
use Psr\Container\ContainerInterface as C;
use UnexpectedValueException;
use WC_Tax;

return [
    'zettle.is-debug' => static function (C $container): bool {
        return defined('WP_DEBUG') && WP_DEBUG;
    },
    'zettle.throw-unhandled-errors' => static function (C $container): bool {
        $envValue = getenv('IZETTLE_THROW_UNHANDLED_ERRORS');

        if ($envValue === false || $envValue === '') { // not specified
            return $container->get('zettle.is-debug');
        }

        return $envValue === '1';
    },
    'zettle.init-possible' => static function (C $container): bool {
        /**
         * The onboarding module will extend this according to the onboarding state
         */
        return false;
    },

    'zettle.requirements.validator' => static function (C $container): ValidatorInterface {
        /** @psalm-suppress PossiblyInvalidArgument */
        return new CompositeValidator([
            $container->get('zettle.requirements.plugins.validator'),
            $container->get('zettle.requirements.extensions.validator'),
        ]);
    },

    'zettle.requirements.plugins.validator' => static function (C $container): ValidatorInterface {
        return new RequiredPluginsValidator(
            $container->get('zettle.requirements.plugins')
        );
    },
    'zettle.requirements.plugins' => static function (): array {
        return [
            'woocommerce/woocommerce.php' => 'WooCommerce',
        ];
    },

    'zettle.requirements.extensions.validator' => static function (C $container): ValidatorInterface {
        return new RequiredExtensionsValidator(
            $container->get('zettle.requirements.extensions')
        );
    },
    'zettle.requirements.extensions' => static function (): array {
        return [
            'mb_strtolower' => 'mbstring',
            'json_encode' => 'json',
            'openssl_get_cipher_methods' => 'openssl',
        ];
    },

    'zettle.is-multisite' => static function (): bool {
        /** @psalm-suppress RedundantCastGivenDocblockType */
        return (bool) is_multisite();
    },
    'zettle.current-site-id' => static function (): int {
        /** @psalm-suppress RedundantCastGivenDocblockType */
        return (int) get_current_blog_id();
    },
    'zettle.wc.shop.location' => static function (): array {
        /** @psalm-suppress RedundantCastGivenDocblockType */
        return (array) wc_get_base_location();
    },
    'zettle.wc.tax.standard-rates' => static function (C $container): array {
        return WC_Tax::find_rates($container->get('zettle.wc.shop.location'));
    },
    'zettle.temp-dir' => static function (): string {
        /** @psalm-suppress RedundantCastGivenDocblockType */
        return (string) get_temp_dir();
    },
    'inpsyde.assets.registry' => static function (C $container): array {
        return [];
    },
    'inpsyde.metabox.registry' => static function (C $container): array {
        return [];
    },
    'zettle.http.page-reloader' => static function (C $container): PageReloaderInterface {
        return new PageReloader();
    },
    'zettle.settings' => static function (): MutableContainerInterface {
        return new WpOptionContainer('woocommerce_zettle_settings');
    },
    'zettle.setup-info' => static function (): MutableContainerInterface {
        return new WpOptionContainer('woocommerce_zettle_info');
    },
    'zettle.sdk.integration-id.container' =>
        static function (C $container): MutableContainerInterface {
            return $container->get('zettle.settings');
        },
    'zettle.oauth.token-storage.container' =>
        static function (C $container): MutableContainerInterface {
            return $container->get('zettle.settings');
        },
    'zettle.webhook.storage.container' =>
        static function (C $container): MutableContainerInterface {
            return $container->get('zettle.settings');
        },
    'zettle.logger' => static function (C $container): CompoundLogger {
        return new CompoundLogger(
            $container->get('zettle.logger.woocommerce'),
            $container->get('zettle.logger.wonolog')
        );
    },
    'zettle.plugin.properties' => static function (): PluginProperties {
        return new PluginProperties(
            __DIR__ . '/../zettle-pos-integration.php',
            __('PayPal Zettle POS', 'zettle-pos-integration')
        );
    },
    'zettle.version-option-key' => static function (): string {
        return 'zettle_pos_integration_version';
    },
    'zettle.clear-cache' => static function (C $container): callable {
        $orgTransientKey = $container->get('zettle.sdk.dal.provider.organization.transient-key');
        return static function () use ($orgTransientKey): void {
            delete_transient($orgTransientKey);
        };
    },

    'zettle.wp.date-format' => static function (C $container): string {
        return get_option('date_format');
    },
    'zettle.wp.time-format' => static function (C $container): string {
        return get_option('time_format');
    },

    'zettle.date-time-format' => static function (C $container): string {
        return $container->get('zettle.wp.time-format') . ' ' . $container->get('zettle.wp.date-format');
    },
    'zettle.format-timestamp' => static function (C $container): callable {
        $format = $container->get('zettle.date-time-format');
        return static function (int $timestamp) use ($format): string {
            if (!is_string($date = wp_date($format, $timestamp))) {
                throw new UnexpectedValueException(sprintf('Cannot get date with format "%1$s" for timestamp "%2$s"', $format, $timestamp));
            }

            return $date;
        };
    },

    'inpsyde.wc-status-report.plugin.name' => static function (C $container): string {
        $plugin = $container->get('zettle.plugin.properties');
        return $plugin->name();
    },
];
