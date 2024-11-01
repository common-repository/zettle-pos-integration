<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Balance;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Balance\LocationBalanceCollection;

interface LocationBalanceCollectionBuilderInterface
{
    /**
     * @param array $data
     *
     * @return LocationBalanceCollection
     */
    public function buildFromArray(array $data): LocationBalanceCollection;

    /**
     * @param LocationBalanceCollection $locationBalanceCollection
     *
     * @return array
     */
    public function createDataArray(LocationBalanceCollection $locationBalanceCollection): array;
}
