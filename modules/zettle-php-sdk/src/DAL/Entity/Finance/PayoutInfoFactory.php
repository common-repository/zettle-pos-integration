<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Finance;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Finance\Type\PeriodType;

class PayoutInfoFactory
{
    /**
     * @param float $totalBalance
     * @param string $currencyId
     * @param float $nextPayoutAmount
     * @param float $discountRemaining
     * @param string $period
     *
     * @return PayoutInfo
     */
    public function create(
        float $totalBalance,
        string $currencyId,
        float $nextPayoutAmount,
        float $discountRemaining,
        string $period
    ): PayoutInfo {

        return new PayoutInfo(
            $totalBalance,
            $currencyId,
            $nextPayoutAmount,
            $discountRemaining,
            PeriodType::get($period)
        );
    }
}
