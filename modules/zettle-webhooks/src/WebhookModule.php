<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Webhooks;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Exception;
use Inpsyde\Zettle\Webhooks\Rest\Endpoint;
use Inpsyde\Zettle\Webhooks\Rest\Verifier;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use WP_CLI;

class WebhookModule implements ModuleInterface
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
        add_action(
            'init',
            function () use ($container) {
                $this->registerRestRoute($container);
                $this->registerCliCommand($container);
            }
        );

        $bootstrap = $container->get('zettle.webhook.bootstrap');
        assert($bootstrap instanceof Bootstrap);

        add_action(
            'zettle-pos-integration.activate',
            static function () use ($bootstrap) {
                $bootstrap->activate();
            }
        );
        add_action(
            'zettle-pos-integration.deactivate',
            static function () use ($bootstrap) {
                $bootstrap->deactivate();
            }
        );
    }

    private function registerCliCommand(ContainerInterface $container)
    {
        if (defined('WP_CLI') && WP_CLI) {
            try {
                WP_CLI::add_command(
                    "zettle webhook",
                    $container->get('zettle.webhook.cli')
                );
            } catch (Exception $exception) {
            }

            return;
        }
    }

    /**
     * Register Listener Webhook Endpoint
     *
     * @param ContainerInterface $container
     */
    private function registerRestRoute(ContainerInterface $container)
    {
        add_action(
            'rest_api_init',
            static function () use ($container) {
                $namespace = $container->get('zettle.webhook.listener.namespace');
                $route = $container->get('zettle.webhook.listener.route');

                $listenerEndpoint = $container->get('zettle.webhook.listener');
                assert($listenerEndpoint instanceof Endpoint);

                $verifier = $container->get('zettle.webhook.verifier');
                assert($verifier instanceof Verifier);

                register_rest_route(
                    $namespace,
                    $route,
                    [
                        'methods' => implode(',', $listenerEndpoint->methods()),
                        'callback' => [$listenerEndpoint, 'callback'],
                        'permission_callback' => [$verifier, 'verify'],
                    ]
                );
            }
        );
    }
}
