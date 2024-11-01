<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\SwishPayment;

interface SwishPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return SwishPayment
     */
    public function buildFromArray(array $data): SwishPayment;

    /**
     * @param SwishPayment $swishPayment
     *
     * @return array
     */
    public function createDataArray(SwishPayment $swishPayment): array;
}
