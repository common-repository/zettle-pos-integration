<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Factory;

// phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
use Inpsyde\Zettle\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface as WcProductRepositoryInterface;
use Inpsyde\Zettle\PhpSdk\Repository\Zettle\Product\ProductRepositoryInterface;
use WC_Product;

class WcProductFactory implements WcProductFactoryInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $izProductRepository;

    /**
     * @var WcProductRepositoryInterface
     */
    private $wcProductRepository;

    /**
     * ProductRepository constructor.
     *
     * @param ProductRepositoryInterface $izProductRepository
     * @param WcProductRepositoryInterface $wcProductRepository
     */
    public function __construct(
        ProductRepositoryInterface $izProductRepository,
        WcProductRepositoryInterface $wcProductRepository
    ) {

        $this->izProductRepository = $izProductRepository;
        $this->wcProductRepository = $wcProductRepository;
    }

    /**
     * @inheritDoc
     */
    public function fromUuid(string $uuid): ?WC_Product
    {
        $productId = $this->izProductRepository->findByUuid($uuid);

        if ($productId === null) {
            return null;
        }

        $product = $this->wcProductRepository->findById($productId);

        if ($product === null) {
            return null;
        }

        return $product;
    }
}
