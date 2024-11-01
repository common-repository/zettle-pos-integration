<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\MobilePayment;

interface MobilePaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return MobilePayment
     */
    public function buildFromArray(array $data): MobilePayment;

    /**
     * @param MobilePayment $mobilePayment
     *
     * @return array
     */
    public function createDataArray(MobilePayment $mobilePayment): array;
}
