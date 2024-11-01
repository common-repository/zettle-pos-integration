<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Discount;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Discount\DiscountCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Discount\DiscountCollectionFactory;

class DiscountCollectionBuilder implements DiscountCollectionBuilderInterface
{
    /**
     * @var DiscountCollectionFactory
     */
    private $discountCollectionFactory;

    /**
     * @var DiscountBuilder
     */
    private $discountBuilder;

    /**
     * DiscountCollectionBuilder constructor.
     *
     * @param DiscountCollectionFactory $discountCollectionFactory
     * @param DiscountBuilderInterface $discountBuilder
     */
    public function __construct(
        DiscountCollectionFactory $discountCollectionFactory,
        DiscountBuilderInterface $discountBuilder
    ) {
        $this->discountCollectionFactory = $discountCollectionFactory;
        $this->discountBuilder = $discountBuilder;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): DiscountCollection
    {
        $discountCollection = $this->discountCollectionFactory->create();

        foreach ($data as $discount) {
            $discountCollection->add(
                $this->discountBuilder->buildFromArray($discount)
            );
        }

        return $discountCollection;
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(DiscountCollection $discountCollection): array
    {
        $data = [];

        foreach ($discountCollection->all() as $discount) {
            $data[][] = $this->discountBuilder->createDataArray($discount);
        }

        return $data;
    }
}
