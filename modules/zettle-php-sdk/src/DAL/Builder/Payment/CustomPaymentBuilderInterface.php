<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\CustomPayment;

interface CustomPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return CustomPayment
     */
    public function buildFromArray(array $data): CustomPayment;

    /**
     * @param CustomPayment $customPayment
     *
     * @return array
     */
    public function createDataArray(CustomPayment $customPayment): array;
}
