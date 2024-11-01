<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Auth;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Inpsyde\Zettle\Auth\OAuth\Token\TokenInterface;
use Inpsyde\Zettle\Auth\OAuth\TokenPersistorInterface;
use Inpsyde\Zettle\Auth\OAuth\TokenProviderInterface;
use Inpsyde\Zettle\Auth\Rest\V1\EndpointInterface;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

class AuthModule implements ModuleInterface
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
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
     */
    public function run(ContainerInterface $container): void
    {
        /**
         * @var TokenProviderInterface|TokenPersistorInterface $tokenStorage
         */
        $tokenStorage = $container->get('zettle.oauth.token-storage');

        add_action(
            'inpsyde.zettle.settings.updated',
            static function (array $changed) use ($container) {
                if (empty($changed)) {
                    return;
                }

                if (isset($changed['api_key'])) {
                    do_action('inpsyde.zettle.credentials.updated', $changed);
                }
            }
        );

        $authFailedKey = $container->get('zettle.auth.is-failed.key');

        add_action(
            'inpsyde.zettle.auth.failed',
            static function () use ($authFailedKey) {
                update_option($authFailedKey, true);
            }
        );
        add_action(
            'inpsyde.zettle.auth.succeeded',
            static function () use ($authFailedKey) {
                delete_option($authFailedKey);
            }
        );

        add_action(
            'inpsyde.zettle.credentials.updated',
            static function (array $changed) use ($container, $authFailedKey) {
                $storage = $container->get('zettle.oauth.token-storage');
                assert($storage instanceof TokenPersistorInterface);

                $storage->clear();

                delete_option($authFailedKey);
            }
        );

        add_action(
            'woocommerce_init',
            static function () use ($container) {
            }
        );

        add_action(
            'inpsyde.zettle.oauth.token-received',
            static function (TokenInterface $token) use ($tokenStorage) {
                $tokenStorage->persist($token);
            }
        );

        add_action(
            'rest_api_init',
            static function () use ($container) {
                $endpoint = $container->get('zettle.oauth.jwt.rest.v1.endpoint.validate');
                assert($endpoint instanceof EndpointInterface);

                register_rest_route(
                    $container->get('zettle.oauth.jwt.rest.namespace'),
                    $endpoint->route(),
                    [
                        'methods' => $endpoint->methods(),
                        'callback' => [$endpoint, 'handleRequest'],
                        'permission_callback' => [$endpoint, 'permissionCallback'],
                        'args' => $endpoint->args(),
                    ]
                );
            }
        );
    }
}
