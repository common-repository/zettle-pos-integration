<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Discount;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Discount\Discount;

interface DiscountBuilderInterface
{
    /**
     * @param array $data
     *
     * @return Discount
     */
    public function buildFromArray(array $data): Discount;

    /**
     * @param Discount $discount
     *
     * @return array
     */
    public function createDataArray(Discount $discount): array;
}
