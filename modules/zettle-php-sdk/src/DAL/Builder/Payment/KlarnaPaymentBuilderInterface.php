<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\KlarnaPayment;

interface KlarnaPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return KlarnaPayment
     */
    public function buildFromArray(array $data): KlarnaPayment;

    /**
     * @param KlarnaPayment $klarnaPayment
     *
     * @return array
     */
    public function createDataArray(KlarnaPayment $klarnaPayment): array;
}
