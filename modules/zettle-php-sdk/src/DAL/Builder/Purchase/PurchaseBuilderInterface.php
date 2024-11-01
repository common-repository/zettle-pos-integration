<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Purchase;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Purchase\Purchase;

interface PurchaseBuilderInterface
{
    /**
     * @param array $data
     *
     * @return Purchase
     */
    public function buildFromArray(array $data): Purchase;

    /**
     * @param Purchase $purchase
     * @return array
     */
    public function createDataArray(Purchase $purchase): array;
}
