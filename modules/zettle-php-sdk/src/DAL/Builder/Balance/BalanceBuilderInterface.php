<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Balance;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Balance\Balance;

interface BalanceBuilderInterface
{
    /**
     * @param array $data
     *
     * @return Balance
     */
    public function buildFromArray(array $data): Balance;

    /**
     * @param Balance $balance
     *
     * @return array
     */
    public function createDataArray(Balance $balance): array;
}
