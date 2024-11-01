<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Inventory;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantInventoryState\VariantInventoryStateCollection;
use Inpsyde\Zettle\PhpSdk\Exception\IdNotFoundException;

final class Inventory
{

    /**
     * @var VariantInventoryStateCollection
     */
    private $variants;

    /**
     * Inventory constructor.
     *
     * @param VariantInventoryStateCollection $variants
     */
    public function __construct(VariantInventoryStateCollection $variants)
    {
        $this->variants = $variants;
    }

    /**
     * @return VariantInventoryStateCollection
     */
    public function variants(): VariantInventoryStateCollection
    {
        return $this->variants;
    }

    /**
     * @param string $variantUuid
     *
     * @return int
     * @throws IdNotFoundException
     */
    public function variantBalance(string $variantUuid): int
    {
        return $this->variants()->get($variantUuid)->balance();
    }
}
