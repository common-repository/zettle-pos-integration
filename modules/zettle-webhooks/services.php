<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Webhooks;

use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Zettle\PhpSdk\API\Webhooks\Entity\RegisteredWebhook;
use Inpsyde\Zettle\Sync\Job\UnlinkProductJob;
use Inpsyde\Zettle\Webhooks\Cli\WebhookCommand;
use Inpsyde\Zettle\Webhooks\Handler\InventoryBalanceChangedHandler;
use Inpsyde\Zettle\Webhooks\Handler\InventoryTrackingStartedHandler;
use Inpsyde\Zettle\Webhooks\Handler\InventoryTrackingStoppedHandler;
use Inpsyde\Zettle\Webhooks\Handler\LogHandler;
use Inpsyde\Zettle\Webhooks\Handler\ProductDeletedHandler;
use Inpsyde\Zettle\Webhooks\Handler\WebhookHandler;
use Inpsyde\Zettle\Webhooks\Job\InventoryBalanceChangedJob;
use Inpsyde\Zettle\Webhooks\Job\WebhookRegistrationJob;
use Inpsyde\Zettle\Webhooks\Rest\SignatureVerifier;
use Inpsyde\Zettle\Webhooks\Rest\WebhookListenerEndpoint;
use Psr\Container\ContainerInterface as C;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;

$job = static function (string $type): string {
    return "zettle.job.{$type}";
};

return [
    $job(InventoryBalanceChangedJob::TYPE) => static function (C $container): Job {
        return new InventoryBalanceChangedJob(
            $container->get('inpsyde.wc-lifecycle-events.products.toggle'),
            $container->get('inpsyde.queue.create-job-record')
        );
    },
    $job(WebhookRegistrationJob::TYPE) => static function (C $container): Job {
        return new WebhookRegistrationJob(
            $container->get('zettle.webhook.register'),
            $container->get('zettle.webhook.can-register')
        );
    },
    'zettle.webhook.config' => static function (C $container): array {
        return [
            'uuid' => Uuid::v1(),
            'eventNames' => $container->get('zettle.webhook.config.events'),
            'destination' => $container->get('zettle.webhook.listener.url'),
            'contactEmail' => $container->get('zettle.webhook.config.email'),

        ];
    },
    'zettle.webhook.config.email' => static function (C $container): string {
        $fromEnv = getenv('IZETTLE_WEBHOOK_EMAIL');
        if ($fromEnv !== false) {
            return $fromEnv;
        }

        return 'zettle-webhooks@inpsyde.com';
    },
    'zettle.webhook.config.events' => static function (): array {
        return [
            'ProductDeleted',
            'InventoryBalanceChanged',
            'InventoryTrackingStarted',
            'InventoryTrackingStopped',
        ];
    },
    'zettle.webhook.listener.namespace' => static function (): string {
        return 'zettle/v1';
    },
    'zettle.webhook.listener.route' => static function (): string {
        return '/webhook/listen';
    },
    'zettle.webhook.listener.url' => static function (C $container): string {
        $namespace = $container->get('zettle.webhook.listener.namespace');
        $route = $container->get('zettle.webhook.listener.route');

        $url = str_replace(
            'http://',
            'https://',
            rest_url("{$namespace}{$route}")
        );

        $ngrokHost = getenv('NGROK_HOST');
        if ($ngrokHost) {
            $host = parse_url($url, PHP_URL_HOST);
            if (!$host) {
                return $url;
            }

            $url = str_replace($host, $ngrokHost, $url);
        }

        return $url;
    },
    'zettle.webhook.listener' => static function (C $container): WebhookListenerEndpoint {
        return new WebhookListenerEndpoint(
            $container->get('zettle.webhook.logger'),
            $container->get('zettle.sdk.api.webhooks.payload.factory'),
            ...$container->get('zettle.webhook.handlers')
        );
    },
    'zettle.webhook.verifier' => static function (C $container): SignatureVerifier {
        return new SignatureVerifier(
            $container->get('zettle.webhook.signing-key')
        );
    },
    'zettle.webhook.registration' => static function (C $container): WebhookRegistration {
        $storage = $container->get('zettle.webhook.storage');
        assert($storage instanceof WebhookStorageInterface);

        return new WebhookRegistration(
            $storage->fetch(),
            $container->get('zettle.sdk.api.webhooks'),
            $container->get('zettle.webhook.deletion'),
            $container->get('zettle.webhook.logger')
        );
    },
    'zettle.webhook.deletion' => static function (C $container): WebhookDeletion {
        $storage = $container->get('zettle.webhook.storage');
        assert($storage instanceof WebhookStorageInterface);

        return new WebhookDeletion(
            $storage->fetch(),
            $container->get('zettle.sdk.api.webhooks'),
            $container->get('zettle.webhook.logger'),
            $container->get('zettle.webhook.can-register')
        );
    },
    'zettle.webhook.storage.option' => static function (C $container): string {
        return 'zettle.webhook.listener';
    },
    'zettle.webhook.storage' => static function (C $container): WebhookStorageInterface {
        return new WebhookStorage(
            $container->get('zettle.sdk.api.webhooks.factory'),
            $container->get('zettle.webhook.storage.container'),
            $container->get('zettle.webhook.storage.option'),
            $container->get('zettle.webhook.config')
        );
    },
    'zettle.webhook.signing-key' => static function (C $container): string {
        $storage = $container->get('zettle.webhook.storage');
        assert($storage instanceof WebhookStorageInterface);
        $webhook = $storage->fetch();

        if ($webhook instanceof RegisteredWebhook) {
            return $webhook->signingKey();
        }

        return '';
    },
    'zettle.webhook.logger' => static function (C $container): LoggerInterface {
        return new NullLogger();
    },
    'zettle.webhook.handlers.product-deleted' =>
        static function (C $container) use ($job): WebhookHandler {
            return new ProductDeletedHandler(
                $container->get($job(UnlinkProductJob::TYPE)),
                $container->get('zettle.webhook.logger')
            );
        },
    'zettle.webhook.handlers.inventory-balance-changed' =>
        static function (C $container) use ($job): WebhookHandler {
            return new InventoryBalanceChangedHandler(
                $container->get($job(InventoryBalanceChangedJob::TYPE)),
                $container->get('zettle.webhook.logger'),
                $container->get('zettle.sdk.id-map.variant'),
                $container->get('zettle.sdk.integration-id')
            );
        },
    'zettle.webhook.handlers' => static function (C $container): array {
        return [
            new LogHandler($container->get('zettle.webhook.logger')),
            new InventoryTrackingStartedHandler(),
            new InventoryTrackingStoppedHandler(),
            $container->get('zettle.webhook.handlers.inventory-balance-changed'),
        ];
    },
    'zettle.webhook.register' => static function (C $container): callable {
        return static function () use ($container): void {
            $webhookStorage = $container->get('zettle.webhook.storage');
            assert($webhookStorage instanceof WebhookStorageInterface);
            $registration = $container->get('zettle.webhook.registration');
            assert($registration instanceof WebhookRegistration);
            $registered = $registration->execute();
            $webhookStorage->persist($registered);
        };
    },
    'zettle.webhook.delete' => static function (C $container): callable {
        return static function () use ($container): void {
            $deletion = $container->get('zettle.webhook.deletion');
            assert($deletion instanceof WebhookDeletion);
            $deletion->execute();
        };
    },
    'zettle.webhook.can-register' => static function (C $container): callable {
        return static function () use ($container): bool {
            $noAuthStates = $container->get('zettle.onboarding.no-auth-states');
            $stateMachine = $container->get('inpsyde.state-machine');
            return !in_array($stateMachine->currentState()->name(), $noAuthStates, true);
        };
    },
    'zettle.webhook.cli' => static function (C $container): WebhookCommand {
        return new WebhookCommand(
            $container->get('zettle.sdk.api.webhooks'),
            $container->get('zettle.webhook.storage'),
            $container->get('zettle.webhook.registration')
        );
    },
    'zettle.webhook.bootstrap' => static function (C $container): Bootstrap {
        return new Bootstrap(
            $container->get('inpsyde.queue.enqueue-job'),
            $container->get('zettle.webhook.delete')
        );
    },
];
