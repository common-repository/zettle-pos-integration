<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Discount;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Discount\DiscountCollection;

interface DiscountCollectionBuilderInterface
{
    /**
     * @param array $data
     *
     * @return DiscountCollection
     */
    public function buildFromArray(array $data): DiscountCollection;

    /**
     * @param DiscountCollection $discountCollection
     *
     * @return array
     */
    public function createDataArray(DiscountCollection $discountCollection): array;
}
