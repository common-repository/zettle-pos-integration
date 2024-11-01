<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding\Counter;

use Inpsyde\Zettle\Onboarding\SyncCollisionStrategy;

class ProductSyncJobsCounter
{
    /**
     * @var callable
     */
    private $productCollectorLocal;

    /**
     * @var string wipe or merge from sync collision strategy settings field
     */
    private $syncCollisionStrategy;

    /**
     * SyncJobsPredictor constructor.
     *
     * @param callable $productCollectorLocal
     * @param string $syncCollisionStrategy
     */
    public function __construct(
        callable $productCollectorLocal,
        string $syncCollisionStrategy
    ) {

        $this->productCollectorLocal = $productCollectorLocal;
        $this->syncCollisionStrategy = $syncCollisionStrategy;
    }

    /**
     * Count Local & Remote Products
     *
     * @return int
     */
    public function count(): int
    {
        /** Starts with 1 because of EnqueueProductsJobs */
        $count = 1;

        if ($this->syncCollisionStrategy === SyncCollisionStrategy::WIPE) {
            // Enqueued WipeRemoteProductsJob
            ++$count;
        }

        $count += count(($this->productCollectorLocal)());

        return $count;
    }
}
