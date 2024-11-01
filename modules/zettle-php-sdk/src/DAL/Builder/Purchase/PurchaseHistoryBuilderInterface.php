<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Purchase;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Purchase\PurchaseHistory;

interface PurchaseHistoryBuilderInterface
{
    /**
     * @param array $data
     *
     * @return PurchaseHistory
     */
    public function buildFromArray(array $data): PurchaseHistory;

    /**
     * @param PurchaseHistory $purchase
     * @return array
     */
    public function createDataArray(PurchaseHistory $purchase): array;
}
