<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Validator;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Variant\StockQuantityAwareInterface;
use Inpsyde\Zettle\PhpSdk\Exception\Validator\MaximumStockException;

// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType

class StockValidator implements ValidatorInterface
{
    /**
     * @var int
     */
    protected $maxStock;

    /**
     * @param int $maxStock
     */
    public function __construct(int $maxStock)
    {
        $this->maxStock = $maxStock;
    }

    /**
     * @inheritDoc
     */
    public function accepts($entity): bool
    {
        return $entity instanceof StockQuantityAwareInterface;
    }

    /**
     * @inheritDoc
     */
    public function validate($entity): bool
    {
        assert($entity instanceof StockQuantityAwareInterface);

        $stock = $entity->defaultQuantity();

        if ($stock > $this->maxStock) {
            throw new MaximumStockException($stock, $this->maxStock);
        }

        return true;
    }
}
