<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Purchase;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Purchase\PurchaseCollection;

interface PurchaseCollectionBuilderInterface
{
    /**
     * @param array $data
     *
     * @return PurchaseCollection
     */
    public function buildFromArray(array $data): PurchaseCollection;

    /**
     * @param PurchaseCollection $purchaseCollection
     *
     * @return array
     */
    public function createDataArray(PurchaseCollection $purchaseCollection): array;
}
