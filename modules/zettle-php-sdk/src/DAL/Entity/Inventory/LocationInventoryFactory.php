<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Inventory;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Balance\LocationBalanceCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Product\ProductCollection;

class LocationInventoryFactory
{
    public function create(
        string $uuid,
        ProductCollection $trackedProducts,
        LocationBalanceCollection $locationBalances
    ): LocationInventory {
        return new LocationInventory(
            $uuid,
            $trackedProducts,
            $locationBalances
        );
    }
}
