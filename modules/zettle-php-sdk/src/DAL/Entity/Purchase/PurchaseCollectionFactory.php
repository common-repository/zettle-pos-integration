<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Purchase;

final class PurchaseCollectionFactory
{
    /**
     * @return PurchaseCollection
     */
    public function create(): PurchaseCollection
    {
        return new PurchaseCollection();
    }
}
