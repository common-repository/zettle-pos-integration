<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\StoreCreditPayment;

interface StoreCreditPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return StoreCreditPayment
     */
    public function buildFromArray(array $data): StoreCreditPayment;

    /**
     * @param StoreCreditPayment $storeCreditPayment
     *
     * @return array
     */
    public function createDataArray(StoreCreditPayment $storeCreditPayment): array;
}
