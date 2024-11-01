<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\CashPayment;

interface CashPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return CashPayment
     */
    public function buildFromArray(array $data): CashPayment;

    /**
     * @param CashPayment $cashPayment
     *
     * @return array
     */
    public function createDataArray(CashPayment $cashPayment): array;
}
