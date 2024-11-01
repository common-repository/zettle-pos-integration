<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Sync\Listener;

use Inpsyde\Zettle\Sync\Job\SetInventoryTrackingJob;
use Inpsyde\Zettle\Sync\VariableInventoryChecker;
use WC_Product_Variable;

/**
 * @see ProductEventListenerRegistry::onPropertyChange() with 'managing_stock'
 */
class VariableManageStockListener
{
    use VariableInventoryChecker;

    /**
     * @var callable
     */
    private $createJob;

    /**
     * @var callable(int):bool
     */
    private $isSyncable;

    public function __construct(
        callable $createJob,
        callable $isSyncable
    ) {

        $this->createJob = $createJob;
        $this->isSyncable = $isSyncable;
    }

    public function __invoke(WC_Product_Variable $new): void
    {
        $productId = (int) $new->get_id();

        if (!($this->isSyncable)($productId)) {
            return;
        }

        ($this->createJob)(
            SetInventoryTrackingJob::TYPE,
            [
                'productId' => $productId,
                'state' => (bool) $new->managing_stock()
                    || $this->hasStockManagingVariations($new),
            ]
        );
    }
}
