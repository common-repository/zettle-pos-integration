<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Logging;

use Inpsyde\Zettle\Logging\Logger\WonoLogger;
use Inpsyde\Zettle\Logging\Logger\WooCommerceLogger;
use Inpsyde\Zettle\Operator\Option\OptionOperatorInterface;
use Psr\Container\ContainerInterface as C;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WC_Logger;

return [
    'zettle.logger.woocommerce.enabled' => static function (C $container): bool {
        return !(bool) getenv('IZETTLE_LOGGING_DISABLE_WOOOCOMMERCE');
    },
    'zettle.logger.woocommerce' => static function (C $container): LoggerInterface {
        if (!$container->get('zettle.logger.woocommerce.enabled')) {
            return new NullLogger();
        }
        if (!class_exists(WC_Logger::class)) {
            return new NullLogger();
        }

        return new WooCommerceLogger(
            wc_get_logger()
        );
    },
    'zettle.logger.wonolog.enabled' => static function (): bool {
        return !(bool) getenv('IZETTLE_LOGGING_DISABLE_WONOLOG');
    },
    'zettle.logger.wonolog.channel' => static function (): string {
        $channel = getenv('IZETTLE_LOGGING_WONOLOG_CHANNEL');
        if (empty($channel)) {
            $channel = 'DEBUG';
        }

        return $channel;
    },
    'zettle.logger.wonolog' => static function (C $container): LoggerInterface {
        if (!$container->get('zettle.logger.wonolog.enabled')) {
            return new NullLogger();
        }
        if (!function_exists('has_action') || !has_action('wonolog.log')) {
            return new NullLogger();
        }
        $channel = $container->get('zettle.logger.wonolog.channel');

        return new WonoLogger($channel);
    },
];
