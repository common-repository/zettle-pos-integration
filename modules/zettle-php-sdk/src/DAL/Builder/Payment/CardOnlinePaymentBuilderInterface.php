<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\CardOnlinePayment;

interface CardOnlinePaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return CardOnlinePayment
     */
    public function buildFromArray(array $data): CardOnlinePayment;

    /**
     * @param CardOnlinePayment $cardOnlinePayment
     *
     * @return array
     */
    public function createDataArray(CardOnlinePayment $cardOnlinePayment): array;
}
