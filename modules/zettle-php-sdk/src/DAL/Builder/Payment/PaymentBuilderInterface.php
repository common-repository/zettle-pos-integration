<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Builder\BuilderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;

interface PaymentBuilderInterface extends BuilderInterface
{
    /**
     * @param array $data
     *
     * @return AbstractPaymentMethod
     */
    public function buildFromArray(array $data): AbstractPaymentMethod;

    /**
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return array
     */
    public function createDataArray(AbstractPaymentMethod $paymentMethod): array;
}
