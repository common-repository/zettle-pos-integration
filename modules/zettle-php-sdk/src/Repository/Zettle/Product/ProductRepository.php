<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Repository\Zettle\Product;

use Inpsyde\Zettle\PhpSdk\Exception\IdNotFoundException;
use Inpsyde\Zettle\PhpSdk\Map\MapRecordCreator;
use Inpsyde\Zettle\PhpSdk\Map\OneToManyMapInterface;
use Inpsyde\Zettle\PhpSdk\Map\OneToOneMapInterface;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @var OneToOneMapInterface|OneToManyMapInterface|MapRecordCreator
     */
    private $productMap;

    /**
     * ProductRepository constructor.
     *
     * @param OneToManyMapInterface $productMap
     */
    public function __construct(OneToManyMapInterface $productMap)
    {
        $this->productMap = $productMap;
    }

    /**
     * @inheritDoc
     */
    public function findByUuid(string $uuid): ?int
    {
        try {
            $productId = $this->productMap->localId($uuid);
        } catch (IdNotFoundException $exception) {
            return null;
        }

        return $productId;
    }

    /**
     * @inheritDoc
     */
    public function findById(int $productId): ?string
    {
        try {
            $uuid = $this->productMap->remoteId($productId);
        } catch (IdNotFoundException $exception) {
            return null;
        }

        return $uuid;
    }
}
