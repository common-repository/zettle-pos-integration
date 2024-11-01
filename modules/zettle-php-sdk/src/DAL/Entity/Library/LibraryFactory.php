<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Library;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Discount\DiscountCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Product\ProductCollection;

class LibraryFactory
{
    public function create(
        string $untilEventLogUuid,
        string $fromEventLogUuid,
        ProductCollection $products,
        DiscountCollection $discounts,
        ?ProductCollection $deletedProducts = null,
        ?DiscountCollection $deletedDiscounts = null
    ): Library {

        return new Library(
            $untilEventLogUuid,
            $fromEventLogUuid,
            $products,
            $discounts,
            $deletedProducts,
            $deletedDiscounts
        );
    }
}
