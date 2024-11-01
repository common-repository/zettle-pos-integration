<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Builder;

use Inpsyde\WcProductContracts\ProductType;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantOption\AttributeSet;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection as Collection;
use Inpsyde\Zettle\PhpSdk\Exception\UnexpectedBuilderPayloadTypeException;
use WC_Product;

class VariantOptionCollectionBuilder implements BuilderInterface
{
    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null): Collection
    {
        if (!$payload instanceof WC_Product) {
            throw new UnexpectedBuilderPayloadTypeException(WC_Product::class, $payload);
        }

        $collection = new Collection();

        if ($payload->is_type(ProductType::SIMPLE)) {
            return $collection;
        }

        if (!$builder) {
            return $collection;
        }

        return $this->setToCollection($builder->build(AttributeSet::class, $payload), $builder);
    }

    private function setToCollection(AttributeSet $set, BuilderInterface $builder): Collection
    {
        $collection = new Collection();

        if (empty($set->all())) {
            return $collection;
        }

        foreach ($set->all() as $type => $attributes) {
            foreach ($attributes as $attribute) {
                $collection->add(
                    $builder->build(VariantOption::class, ['name' => $type, 'value' => $attribute])
                );
            }
        }

        return $collection;
    }
}
