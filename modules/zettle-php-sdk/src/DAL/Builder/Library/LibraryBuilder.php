<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Library;

use Inpsyde\Zettle\PhpSdk\DAL\Builder\Discount\DiscountCollectionBuilder;
use Inpsyde\Zettle\PhpSdk\DAL\Builder\Discount\DiscountCollectionBuilderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Builder\Product\ProductCollectionBuilder;
use Inpsyde\Zettle\PhpSdk\DAL\Builder\Product\ProductCollectionBuilderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Library\Library;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Library\LibraryFactory;

class LibraryBuilder implements LibraryBuilderInterface
{
    /**
     * @var LibraryFactory
     */
    private $libraryFactory;

    /**
     * @var ProductCollectionBuilder
     */
    private $productCollectionBuilder;

    /**
     * @var DiscountCollectionBuilder
     */
    private $discountCollectionBuilder;

    /**
     * LibraryBuilder constructor.
     *
     * @param LibraryFactory $libraryFactory
     * @param ProductCollectionBuilderInterface $productCollectionBuilder
     * @param DiscountCollectionBuilderInterface $discountCollectionBuilder,
     */
    public function __construct(
        LibraryFactory $libraryFactory,
        ProductCollectionBuilderInterface $productCollectionBuilder,
        DiscountCollectionBuilderInterface $discountCollectionBuilder
    ) {
        $this->libraryFactory = $libraryFactory;
        $this->productCollectionBuilder = $productCollectionBuilder;
        $this->discountCollectionBuilder = $discountCollectionBuilder;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): Library
    {
        return $this->build($data);
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(Library $library): array
    {
        $data = [
            'untilEventLogUuid' => (string) $library->untilEventLogUuid(),
            'fromEventLogUuid' => (string) $library->fromEventLogUuid(),
            'products' => $this->productCollectionBuilder->createDataArray($library->products()),
            'discounts' => $this->discountCollectionBuilder->createDataArray($library->discounts()),
        ];

        if ($library->deletedProducts()) {
            $data['deletedProducts'] = $this->productCollectionBuilder->createDataArray(
                $library->deletedProducts()
            );
        }

        if ($library->deletedDiscounts()) {
            $data['deletedDiscounts'] = $this->discountCollectionBuilder->createDataArray(
                $library->deletedDiscounts()
            );
        }

        return $data;
    }

    /**
     * @param array $data
     * @return Library
     */
    private function build(array $data): Library
    {
        $deletedProducts = $data['deletedProducts'] ? $this->productCollectionBuilder->buildFromArray(
            $data['deletedProducts']
        ) : null;
        $deletedDiscounts = $data['deletedDiscounts'] ? $this->discountCollectionBuilder->buildFromArray(
            $data['deletedDiscounts']
        ) : null;

        return $this->libraryFactory->create(
            $data['untilEventLogUuid'],
            $data['fromEventLogUuid'],
            $this->productCollectionBuilder->buildFromArray($data['products']),
            $this->discountCollectionBuilder->buildFromArray($data['discounts']),
            $deletedProducts,
            $deletedDiscounts
        );
    }
}
