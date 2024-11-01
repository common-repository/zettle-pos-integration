<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Filter;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Product\LazyProduct;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Product\Product;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Product\ProductInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Product\ProductTransferInterface;
use Inpsyde\Zettle\PhpSdk\Exception\IdNotFoundException;
use Inpsyde\Zettle\PhpSdk\Map\OneToOneMapInterface;
use WC_Product;

// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType

/**
 * Class ProductConnectionFilter
 *
 * This filter connects an Zettle Product with its WooCommerce counterpart
 * by querying an ID mapping table
 *
 * @package Inpsyde\Zettle\PhpSdk\Filter
 */
class ProductConnectionFilter implements FilterInterface
{

    /**
     * @var OneToOneMapInterface
     */
    private $idMap;

    private $lazyPool = [];

    /**
     * @var callable
     */
    private $productClientProvider;

    public function __construct(
        OneToOneMapInterface $idMap,
        callable $productClientProvider
    ) {

        $this->idMap = $idMap;
        $this->productClientProvider = $productClientProvider;

        add_action('zettle.clear-product-cache', function () {
            $this->lazyPool = [];
        });
    }

    /**
     * @inheritDoc
     */
    public function accepts($entity, $payload): bool
    {
        return $entity instanceof Product and $payload instanceof WC_Product;
    }

    /**
     * @inheritDoc
     */
    public function filter($product, $wcProduct)
    {
        assert($wcProduct instanceof WC_Product);
        assert($product instanceof ProductTransferInterface);
        $localId = $wcProduct->get_id();

        try {
            $remoteId = $this->idMap->remoteId($localId);
            $product->setUuid($remoteId);
        } catch (IdNotFoundException $exception) {
            $product = $this->getLazyProduct($localId, $product);
        }

        return $product;
    }

    private function getLazyProduct(
        int $localId,
        ProductTransferInterface $product
    ): ProductInterface {

        if (!isset($this->lazyPool[$localId])) {
            $productClient = ($this->productClientProvider)();
            $this->lazyPool[$localId] = new LazyProduct($localId, $product, $productClient, $this->idMap);
        }

        return $this->lazyPool[$localId];
    }
}
