<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Balance;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Balance\LocationBalance;

interface LocationBalanceBuilderInterface
{
    /**
     * @param array $data
     *
     * @return LocationBalance
     */
    public function buildFromArray(array $data): LocationBalance;

    /**
     * @param LocationBalance $locationBalance
     * @return array
     */
    public function createDataArray(LocationBalance $locationBalance): array;
}
