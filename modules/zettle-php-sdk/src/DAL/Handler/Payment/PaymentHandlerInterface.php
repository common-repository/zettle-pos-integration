<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Handler\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;

interface PaymentHandlerInterface
{
    /**
     * @param string $paymentType
     *
     * @return bool
     */
    public function accepts(string $paymentType): bool;

    /**
     * @param AbstractPaymentMethod $payment
     *
     * @return array
     */
    public function serialize(AbstractPaymentMethod $payment): array;

    /**
     * @param array $data
     *
     * @return AbstractPaymentMethod
     */
    public function deserialize(array $data): AbstractPaymentMethod;
}
