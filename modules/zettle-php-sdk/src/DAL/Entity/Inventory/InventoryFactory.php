<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Inventory;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantChangeHistory\VariantInventoryState;

class InventoryFactory
{
    /**
     * @param VariantInventoryState $variants
     *
     * @return Inventory
     */
    public function create(
        VariantInventoryState $variants
    ): Inventory {
        return new Inventory(
            $variants
        );
    }
}
