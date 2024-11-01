<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Balance;

class BalanceFactory
{
    /**
     * @param string $productUuid
     * @param string $variantUuid
     * @param string $balance
     *
     * @return Balance
     */
    public function create(
        string $productUuid,
        string $variantUuid,
        string $balance
    ): Balance {

        return new Balance(
            $productUuid,
            $variantUuid,
            (int) $balance
        );
    }
}
