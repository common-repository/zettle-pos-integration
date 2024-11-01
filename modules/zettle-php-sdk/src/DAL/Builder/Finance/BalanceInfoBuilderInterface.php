<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Finance;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Finance\BalanceInfo;

interface BalanceInfoBuilderInterface
{
    /**
     * @param array $data
     *
     * @return BalanceInfo
     */
    public function buildFromArray(array $data): BalanceInfo;

    /**
     * @param BalanceInfo $balanceInfo
     *
     * @return array
     */
    public function createDataArray(BalanceInfo $balanceInfo): array;
}
