<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Sync;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Exception;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use WP_CLI;

class SyncModule implements ModuleInterface
{

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
    public function run(ContainerInterface $container): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            try {
                WP_CLI::add_command(
                    "zettle sync",
                    $container->get('zettle.sync.cli.sync-product')
                );
                WP_CLI::add_command(
                    "zettle unlink",
                    $container->get('zettle.sync.cli.unlink-product')
                );
                WP_CLI::add_command(
                    "zettle reset",
                    $container->get('zettle.sync.cli.reset')
                );
                WP_CLI::add_command(
                    "zettle export",
                    $container->get('zettle.sync.cli.export')
                );
                WP_CLI::add_command(
                    'zettle exclude',
                    $container->get('zettle.sync.cli.exclude')
                );
            } catch (Exception $exception) {
            }
        }

        $logger = $container->get('zettle.logger');

        // without is_admin it triggers multiple time in ajax requests
        // also to avoid performance issues for users
        if (
            is_admin()
            && $container->get('zettle.sync.price-sync-enabled')
            && !$container->get('zettle.auth.is-failed')
        ) {
            try {
                $settings = $container->get('zettle.settings');

                $storeComparison = $container->get('zettle.onboarding.comparison.store');
                if (!$storeComparison->canSyncPrices()) {
                    $logger->info(__(
                        'Cannot sync prices with PayPal Zettle anymore, check your WC settings (currency, country, taxes).',
                        'zettle-pos-integration'
                    ));

                    $settings->set('sync_price_strategy', PriceSyncMode::DISABLED);
                }
            } catch (Exception $exception) {
                // likely happens on auth failure when refreshing account data
                $logger->debug('Settings check failed. ' . $exception->getMessage());
            }
        }
    }
}
