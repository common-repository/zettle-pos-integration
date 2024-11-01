<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Assets;

use Inpsyde\Zettle\Onboarding\SyncCollisionStrategy;
use Inpsyde\Zettle\Sync\Job\EnqueueProductSyncJob;
use Inpsyde\Zettle\Sync\Job\ExportProductJob;
use Inpsyde\Zettle\Sync\Job\WipeRemoteProductsJob;
use Psr\Container\ContainerInterface as C;

return [
    'zettle.assets.sync-job-types' => static function (C $container): array {
        $jobTypes = [
            'prepare' => [
                EnqueueProductSyncJob::TYPE,
            ],
            'sync' => [
                ExportProductJob::TYPE,
            ],
        ];
        $settings = $container->get('zettle.settings');

        if ($settings->has('sync_collision_strategy')) {
            $collisionStrategy = $settings->get('sync_collision_strategy');

            if ($collisionStrategy === SyncCollisionStrategy::WIPE) {
                $jobTypes['prepare'][] = WipeRemoteProductsJob::TYPE;
            }
        }

        return $jobTypes;
    },
    'zettle.assets.should-enqueue.all' => static function (C $container): callable {
        return static function () use ($container): bool {
            return true;
        };
    },
    'zettle.assets.should-enqueue.sync-module' => static function (C $container): callable {
        return static function () use ($container): bool {
            return $container->get('zettle.assets.should-enqueue.all')();
        };
    },
];
