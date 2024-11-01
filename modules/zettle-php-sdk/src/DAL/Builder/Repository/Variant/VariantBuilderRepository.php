<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Repository\Variant;

use Inpsyde\Zettle\PhpSdk\Builder\BuilderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Variant\VariantInterface;
use WC_Product;

class VariantBuilderRepository implements VariantBuilderRepositoryInterface
{
    /**
     * @var BuilderInterface
     */
    private $builder;

    /**
     * VariantBuilderRepository constructor.
     *
     * @param BuilderInterface $builder
     */
    public function __construct(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @inheritDoc
     */
    public function addToCollection(
        WC_Product $wcProduct,
        VariantCollection $collection
    ): VariantCollection {
        $variant = $this->builder->build(VariantInterface::class, $wcProduct);

        $collection->add($variant);

        return $collection;
    }
}
