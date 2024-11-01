<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Repository\Variant;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Inpsyde\Zettle\PhpSdk\Exception\BuilderException;
use WC_Product;

interface VariantBuilderRepositoryInterface
{
    /**
     * @param WC_Product $wcProduct
     * @param VariantCollection $collection
     *
     * @return VariantCollection
     *
     * @throws BuilderException If failed to build Variant from WC
     */
    public function addToCollection(
        WC_Product $wcProduct,
        VariantCollection $collection
    ): VariantCollection;
}
