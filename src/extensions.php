<?php

declare(strict_types=1);

namespace Inpsyde\Zettle;

use Exception;
use Inpsyde\Debug\DebugProxyFactory;
use Inpsyde\WcStatusReport\ReportItemFactoryInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Organization\Organization;
use Inpsyde\Zettle\PhpSdk\Psr18RestClient;
use Psr\Container\ContainerInterface as C;
use Psr\Log\LoggerInterface;
use Throwable;

return [
    'inpsyde.debug.logger' => static function (
        C $container,
        LoggerInterface $previous
    ): LoggerInterface {
        return $container->get('zettle.logger')->addLogger($previous);
    },
    'inpsyde.queue.logger' => static function (
        C $container,
        LoggerInterface $previous
    ): LoggerInterface {
        return $container->get('zettle.logger')->addLogger($previous);
    },
    'zettle.webhook.logger' => static function (
        C $container,
        LoggerInterface $previous
    ): LoggerInterface {
        return $container->get('zettle.logger')->addLogger($previous);
    },
    /**
     * Wire up the Zettle Settings module to the Auth module
     * by passing the SettingsContainer into the CredentialsContainer
     */
    'zettle.oauth.credentials.parent' => static function (
        C $container
    ): C {
        return $container->get('zettle.settings');
    },
    'zettle.sdk.rest-client' =>
        static function (C $container, Psr18RestClient $client): Psr18RestClient {
            $proxyFactory = $container->get('inpsyde.debug.proxy-factory');
            assert($proxyFactory instanceof DebugProxyFactory);

            return $proxyFactory->forInstanceMethods($client);
        },
    'inpsyde.queue.exception-handler' =>
        static function (C $container, callable $previous): callable {
            return static function (Throwable $exception) use ($previous, $container) {
                $previous($exception);
                $container->get('inpsyde.debug.exception-handler')->handle($exception);
            };
        },

    'inpsyde.wc-status-report.items' => static function (C $container, array $items): array {
        $factory = $container->get('inpsyde.wc-status-report.item-factory');
        assert($factory instanceof ReportItemFactoryInterface);

        $settings = $container->get('zettle.settings');
        assert($settings instanceof C);

        $state = $settings->has('onboarding.current-state') ? $settings->get('onboarding.current-state') : '';

        $items[] = $factory->createReportItem(
            __('Onboarding state', 'zettle-pos-integration'),
            'Onboarding state',
            $state
        );

        $preAuthStates = $container->get('zettle.onboarding.pre-auth-states');
        if (!$state || in_array($state, $preAuthStates, true)) {
            return $items;
        }

        $items[] = $factory->createReportItem(
            __('Price sync', 'zettle-pos-integration'),
            'Price sync',
            $container->get('zettle.sync.price-sync-enabled') ? 'yes' : 'no'
        );
        $items[] = $factory->createReportItem(
            __('Initial sync collision strategy', 'zettle-pos-integration'),
            'Initial sync collision strategy',
            $settings->has('sync_collision_strategy') ? $settings->get('sync_collision_strategy') : ''
        );

        $firstImportTimestamp = $container->get('zettle.onboarding.first-import-timestamp');
        if ($firstImportTimestamp) {
            $items[] = $factory->createReportItem(
                __('First import', 'zettle-pos-integration'),
                'First import',
                $container->get('zettle.format-timestamp')($firstImportTimestamp)
            );
        }

        $productCounter = $container->get('zettle.sdk.id-map.product');
        try {
            $items[] = $factory->createReportItem(
                __('Number of products syncing', 'zettle-pos-integration'),
                'Number of products syncing',
                $productCounter->count()
            );
        } catch (Exception $exc) {
            $container->get('zettle.logger')
                ->warning(sprintf('Failed to load number of products syncing: %1$s', $exc->getMessage()));
        }

        try {
            $org = $container->get('zettle.sdk.dal.provider.organization')->provide();
            assert($org instanceof Organization);

            if ($org->vat()) {
                $items[] = $factory->createReportItem(
                    __('PayPal Zettle VAT', 'zettle-pos-integration'),
                    'PayPal Zettle VAT',
                    $org->vat()->percentage()
                );
            }
            $items[] = $factory->createReportItem(
                __('PayPal Zettle currency', 'zettle-pos-integration'),
                'PayPal Zettle currency',
                $org->currency()
            );
            $items[] = $factory->createReportItem(
                __('PayPal Zettle country', 'zettle-pos-integration'),
                'PayPal Zettle country',
                $org->country()
            );
            $items[] = $factory->createReportItem(
                __('PayPal Zettle language', 'zettle-pos-integration'),
                'PayPal Zettle language',
                $org->language()
            );
            $timezone = $org->timeZone();
            if ($timezone) {
                $items[] = $factory->createReportItem(
                    __('PayPal Zettle time zone', 'zettle-pos-integration'),
                    'PayPal Zettle time zone',
                    $timezone->getName()
                );
            }
            $items[] = $factory->createReportItem(
                __('PayPal Zettle contact email', 'zettle-pos-integration'),
                'PayPal Zettle contact email',
                $org->contactEmail()
            );
            $items[] = $factory->createReportItem(
                __('PayPal Zettle taxation mode', 'zettle-pos-integration'),
                'PayPal Zettle taxation mode',
                $org->taxationMode()
            );
            $items[] = $factory->createReportItem(
                __('PayPal Zettle taxation type', 'zettle-pos-integration'),
                'PayPal Zettle taxation type',
                $org->taxationType()
            );
            $items[] = $factory->createReportItem(
                __('PayPal Zettle customer mode', 'zettle-pos-integration'),
                'PayPal Zettle customer mode',
                $org->customerType()
            );
        } catch (Exception $exc) {
            $container->get('zettle.logger')
                ->warning(sprintf('Failed to load PayPal Zettle account info: %1$s', $exc->getMessage()));
        }

        return $items;
    },
];
