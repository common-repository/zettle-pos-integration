<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\GiftCardPayment;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Inpsyde\Zettle\PhpSdk\DAL\Exception\EntityFactoryException;

class GiftcardPaymentBuilder implements GiftcardPaymentBuilderInterface
{
    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * CardOnlinePaymentBuilder constructor.
     *
     * @param PaymentFactory $paymentFactory
     */
    public function __construct(PaymentFactory $paymentFactory)
    {
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(GiftCardPayment $giftCardPayment): array
    {
        return [
            'uuid' => (string) $giftCardPayment->uuid(),
            'type' => $giftCardPayment->type()->getValue(),
            'amount' => $giftCardPayment->amount(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): GiftCardPayment
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return GiftCardPayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): GiftCardPayment
    {
        return $this->paymentFactory->createGiftcardPayment(
            $data['uuid'],
            $data['type'],
            $data['amount']
        );
    }
}
