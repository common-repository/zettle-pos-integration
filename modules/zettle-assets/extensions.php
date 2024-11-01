<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Assets;

use Inpsyde\Assets\Asset;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\Zettle\Auth\Rest\V1\ValidationEndpoint;
use Inpsyde\Zettle\Onboarding\Counter\ProductSyncJobsCounter;
use Psr\Container\ContainerInterface as C;

return [
    'inpsyde.assets.registry' => static function (C $container, array $previous): array {
        $assetUri = rtrim(plugins_url('/assets/', __DIR__ . '/zettle-pos-integration.php'), '/\\');

        if ($container->get('zettle.assets.should-enqueue.all')()) {
            $previous[] = (new Style(
                'zettle-admin-style',
                "{$assetUri}/admin.css",
                Asset::BACKEND
            ));

            $previous[] = (new Script(
                'zettle-admin-scripts',
                "{$assetUri}/admin-scripts.js",
                Asset::BACKEND
            ))
                ->withLocalize(
                    'zettleAPIKeyCreation',
                    static function () use ($container): array {
                        $url = $container->get('zettle.settings.account.link.api-key-creation-url');

                        return [
                            'url' => $url,
                        ];
                    }
                )
                ->withLocalize(
                    'zettleOnboardingValidationRules',
                    static function () use ($container): array {
                        return [
                            'woocommerce_zettle_api_key' => [
                                'required' => [
                                    'message' => __('Enter the API key.', 'zettle-pos-integration'),
                                ],
                                'remote' => [
                                    'url' => $container->get('zettle.oauth.jwt.rest.url'),
                                    'valueParamName',
                                    'requestMethod' => 'GET',
                                    'resultPropertyName' => 'result',
                                    'skippedErrors' => [ValidationEndpoint::ERROR_WRITE_ONLY_PASSWORD_NOT_FILLED],
                                    'nonce' => wp_create_nonce('wp_rest'),
                                    'message' => __(
                                        'The API key is not valid.',
                                        'zettle-pos-integration'
                                    ),
                                ],
                            ],
                        ];
                    }
                )
                ->withLocalize(
                    'zettleDisconnection',
                    static function () use ($container): array {
                        return [
                            'url' => $container->get('zettle.onboarding.disconnect.endpoint.url'),
                            'dialogId' => $container->get('zettle.settings.account.disconnect.id'),
                            'nonce' => wp_create_nonce('wp_rest'),
                        ];
                    }
                );
        }

        if ($container->get('zettle.assets.should-enqueue.sync-module')()) {
            $previous[] = (new Script(
                'zettle-sync-scripts',
                "{$assetUri}/sync-scripts.js",
                Asset::BACKEND
            ))
                ->withLocalize(
                    'zettleQueueProcessEndpoint',
                    static function () use ($container): array {
                        $jobTypes = $container->get('zettle.assets.sync-job-types');
                        $jobRepo = $container->get('inpsyde.queue.repository');
                        assert($jobRepo instanceof JobRepository);
                        $productSyncJobsCounter = $container->get('zettle.onboarding.counter.product.sync');
                        assert($productSyncJobsCounter instanceof ProductSyncJobsCounter);

                        return [
                            'nonce' => wp_create_nonce('wp_rest'),
                            'url' => $container->get('inpsyde.queue.rest.v1.endpoint.process.url'),
                            'requestArguments' => [
                                'meta' => [
                                    'active' => true,
                                    'value' => ['zettle-onboarding' => true],
                                ],
                            ],
                            'messages' => [
                                'error' => __(
                                    'There was an unexpected error while syncing products. Please check your logs and contact support.',
                                    'zettle-pos-integration'
                                ),
                                'confirmCancel' => __(
                                    'Are you sure you want to cancel? You will be able to re-enter your sync settings and then start again.',
                                    'zettle-pos-integration'
                                ),
                                'finished' => __(
                                    'Synchronization finished.',
                                    'zettle-pos-integration'
                                ),
                                'status' => [
                                    'prepare' => __(
                                        'Preparing to sync products',
                                        'zettle-pos-integration'
                                    ),
                                    'sync' => __(
                                        'Synchronization in progress',
                                        'zettle-pos-integration'
                                    ),
                                    'cleanup' => __('Cleaning up', 'zettle-pos-integration'),
                                ],
                            ],
                            'jobTypes' => $jobTypes,
                        ];
                    }
                );
        }

        return $previous;
    },
];
