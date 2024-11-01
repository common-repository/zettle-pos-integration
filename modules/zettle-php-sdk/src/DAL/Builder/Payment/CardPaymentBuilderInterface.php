<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\CardPayment;

interface CardPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return CardPayment
     */
    public function buildFromArray(array $data): CardPayment;

    /**
     * @param CardPayment $cardPayment
     *
     * @return array
     */
    public function createDataArray(CardPayment $cardPayment): array;
}
