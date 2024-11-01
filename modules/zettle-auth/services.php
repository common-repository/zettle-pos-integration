<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Auth;

use Http\Client\Common\Plugin;
use Inpsyde\Zettle\Auth\HTTPlug\ChaosMonkeyPlugin;
use Inpsyde\Zettle\Auth\HTTPlug\ZettleAuthPlugin;
use Inpsyde\Zettle\Auth\Jwt\ParserFactory;
use Inpsyde\Zettle\Auth\Jwt\ParserFactoryInterface;
use Inpsyde\Zettle\Auth\Jwt\ParserInterface;
use Inpsyde\Zettle\Auth\OAuth\CredentialValidator;
use Inpsyde\Zettle\Auth\OAuth\Grant\GrantType;
use Inpsyde\Zettle\Auth\OAuth\Grant\JwtGrant;
use Inpsyde\Zettle\Auth\OAuth\ZettleOAuthHeader;
use Inpsyde\Zettle\Auth\OAuth\ContainerTokenStorage;
use Inpsyde\Zettle\Auth\OAuth\Token\TokenFactory;
use Inpsyde\Zettle\Auth\OAuth\TokenPersistingAuthSuccessHandler;
use Inpsyde\Zettle\Auth\Rest\V1\EndpointInterface;
use Inpsyde\Zettle\Auth\Rest\V1\ValidationEndpoint;
use Inpsyde\Zettle\Auth\Validator\Validator;
use Inpsyde\Zettle\Auth\Validator\ValidatorInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Container\ContainerInterface as C;

$wire = static function (string ...$parts): callable {
    $class = array_shift($parts);

    //phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
    return static function (C $container) use ($class, $parts) {
        return new $class(
            ...array_map(
                static function (string $key) use ($container) {
                    return $container->get($key);
                },
                $parts
            )
        );
    };
    //phpcs:enable
};

return [
    'zettle.oauth.token-storage.key' => static function (C $container): string {
        return 'api_token';
    },
    'zettle.oauth.token-storage' => static function (C $container): ContainerTokenStorage {
        return new ContainerTokenStorage(
            // Must be satisfied externally. Maybe throw a special exception?
            $container->get('zettle.oauth.token-storage.container'),
            $container->get('zettle.oauth.token-storage.key'),
            new TokenFactory()
        );
    },
    'zettle.oauth.authentication' => $wire(
        ZettleOAuthHeader::class,
        'zettle.oauth.token-storage'
    ),
    'zettle.oauth.credentials.parent' => static function (C $container) {
        return null;
    },
    'zettle.oauth.credentials' => static function (C $container): ContainerInterface {
        return new CredentialsContainer(
            $container->get('zettle.oauth.jwt.parser'),
            [],
            $container->get('zettle.oauth.credentials.parent')
        );
    },
    'zettle.oauth.auth-grant' => static function (C $container): GrantType {
        return $container->get('zettle.oauth.grant.api');
    },
    'zettle.oauth.refresh-grant' => static function (C $container): GrantType {
        return $container->get('zettle.oauth.grant.api');
    },
    'zettle.oauth.grant.api' => $wire(
        JwtGrant::class,
        'zettle.oauth.credentials',
        'zettle.oauth.jwt.parser',
        'zettle.oauth.client-id'
    ),
    'zettle.http-plug.plugin' => static function (C $container): Plugin {
        return new ZettleAuthPlugin(
            $container->get('zettle.oauth.authentication'),
            static function (RequestInterface $request): bool {
                $host = $request->getUri()->getHost();
                $path = $request->getUri()->getPath();

                if (!preg_match('/.*\.izettle\.com/', $host)) {
                    return false;
                }

                if ($host === 'oauth.izettle.com' && $path !== '/users/me') {
                    return false;
                }

                return true;
            },
            $container->get('inpsyde.http-client.uri-factory'),
            $container->get('inpsyde.http-client.stream-factory'),
            $container->get('zettle.oauth.auth-grant'),
            $container->get('zettle.oauth.refresh-grant'),
            new TokenPersistingAuthSuccessHandler(
                $container->get('zettle.oauth.token-storage'),
                new TokenFactory()
            )
        );
    },
    'zettle.http-plug.plugin.chaos-monkey' => static function (C $container): Plugin {
        return new ChaosMonkeyPlugin(
            $container->get('inpsyde.http-client.response-factory'),
            $container->get('inpsyde.http-client.stream-factory')
        );
    },

    'zettle.auth.is-failed' => static function (C $container): bool {
        return (bool) get_option($container->get('zettle.auth.is-failed.key'));
    },
    'zettle.auth.is-failed.key' => static function (): string {
        return 'zettle-pos-integration.auth-failed';
    },

    'zettle.oauth.http-client-factory' =>
        static function (C $container): AuthenticatedClientFactory {
            return new AuthenticatedClientFactory(
                $container->get('inpsyde.http-client.factory'),
                $container->get('inpsyde.http-client.uri-factory'),
                $container->get('inpsyde.http-client.stream-factory'),
                $container->get('zettle.oauth.jwt.parser'),
                $container->get('zettle.oauth.headers.partner-affiliation'),
                $container->get('zettle.oauth.client-id')
            );
        },
    'zettle.oauth.credential-validator' => static function (C $container): CredentialValidator {
        return new CredentialValidator(
            $container->get('zettle.oauth.http-client-factory'),
            $container->get('inpsyde.http-client.request-factory')
        );
    },
    'zettle.oauth.client-id' => static function (): string {
        return 'de149dc7-44b5-4390-ab64-88e301771f06';
    },
    'zettle.oauth.headers.partner-affiliation' => static function (C $container): array {
        return [
            'X-iZettle-Application-Id' => $container->get('zettle.oauth.client-id'),
        ];
    },
    'zettle.oauth.jwt.parser' => static function (C $container): ParserInterface {
        $factory = $container->get('zettle.oauth.jwt.parser.factory');
        assert($factory instanceof ParserFactoryInterface);

        return $factory->createParser();
    },
    'zettle.oauth.jwt.parser.factory' => static function (C $container): ParserFactoryInterface {
        return new ParserFactory();
    },
    'zettle.oauth.jwt.validator' => static function (C $container): ValidatorInterface {
        return new Validator(
            $container->get('zettle.oauth.jwt.parser')
        );
    },
    'zettle.oauth.jwt.namespace' => static function (): string {
        return 'zettle';
    },
    'zettle.oauth.jwt.rest.namespace' => static function (C $container): string {
        $namespace = $container->get('zettle.oauth.jwt.namespace');
        $endpoint = $container->get('zettle.oauth.jwt.rest.v1.endpoint.validate');

        return "{$namespace}-jwt/{$endpoint->version()}";
    },
    'zettle.oauth.jwt.rest.url' => static function (C $container): string {
        $namespace = $container->get('zettle.oauth.jwt.rest.namespace');
        $endpoint = $container->get('zettle.oauth.jwt.rest.v1.endpoint.validate');

        return rest_url("{$namespace}{$endpoint->route()}");
    },
    'zettle.oauth.jwt.rest.v1.endpoint.validate' =>
        static function (C $container): EndpointInterface {
            return new ValidationEndpoint(
                $container->get('zettle.oauth.jwt.validator'),
                $container->get('zettle.onboarding.settings.write-only-password-field-checker')
            );
        },
];
