<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\VippsPayment;

interface VippsPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return VippsPayment
     */
    public function buildFromArray(array $data): VippsPayment;

    /**
     * @param VippsPayment $vippsPayment
     *
     * @return array
     */
    public function createDataArray(VippsPayment $vippsPayment): array;
}
