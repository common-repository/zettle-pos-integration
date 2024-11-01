<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Auth;

use Http\Client\Common\Plugin\HeaderSetPlugin;
use Inpsyde\Zettle\Onboarding\OnboardingState;
use Psr\Container\ContainerInterface;

return [
    'inpsyde.http-client.plugins' =>
        static function (ContainerInterface $container, array $previous): array {
            $previous[] = $container->get('zettle.http-plug.plugin');

            if (getenv('IZETTLE_CHAOS_MONKEY_ENABLED') === '1') {
                $previous[] = $container->get('zettle.http-plug.plugin.chaos-monkey');
            }

            $previous[] = new HeaderSetPlugin(
                $container->get('zettle.oauth.headers.partner-affiliation')
            );

            return $previous;
        },
    'zettle.settings.fields.registry' =>
        static function (ContainerInterface $container, array $previous): array {
            return array_merge(
                $previous,
                [
                    'authentication' => [
                        'title' => __('Authentication', 'zettle-pos-integration'),
                        'type' => 'title',
                        'description' => __(
                            'Credentials needed for communicating with your PayPal Zettle store via its API',
                            'zettle-pos-integration'
                        ),
                    ],
                    'api_key' => [
                        'title' => __('API key', 'zettle-pos-integration'),
                        'type' => 'zettle-writeonly-password',
                        'description' => __(
                            'Enter the API key you created through PayPal Zettle.',
                            'zettle-pos-integration'
                        ),
                        'desc_tip' => true,
                        'default' => '',
                        'custom_attributes' => [
                            'autocomplete' => 'off',
                        ],
                    ],
                ]
            );
        },
];
