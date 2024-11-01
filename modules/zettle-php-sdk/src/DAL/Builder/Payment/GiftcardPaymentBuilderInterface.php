<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\GiftCardPayment;

interface GiftcardPaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return GiftCardPayment
     */
    public function buildFromArray(array $data): GiftCardPayment;

    /**
     * @param GiftCardPayment $giftCardPayment
     *
     * @return array
     */
    public function createDataArray(GiftCardPayment $giftCardPayment): array;
}
