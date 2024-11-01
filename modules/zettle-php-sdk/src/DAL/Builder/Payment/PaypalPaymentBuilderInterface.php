<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\PaypalPayment;

interface PaypalPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return PaypalPayment
     */
    public function buildFromArray(array $data): PaypalPayment;

    /**
     * @param PaypalPayment $paypalPayment
     *
     * @return array
     */
    public function createDataArray(PaypalPayment $paypalPayment): array;
}
