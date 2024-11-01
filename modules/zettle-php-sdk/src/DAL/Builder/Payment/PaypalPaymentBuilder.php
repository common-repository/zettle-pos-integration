<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\PaypalPayment;
use Inpsyde\Zettle\PhpSdk\DAL\Exception\EntityFactoryException;

class PaypalPaymentBuilder implements PaypalPaymentBuilderInterface
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
    public function createDataArray(PaypalPayment $paypalPayment): array
    {
        return [
            'uuid' => (string) $paypalPayment->uuid(),
            'amount' => $paypalPayment->amount(),
            'type' => $paypalPayment->type()->getValue(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): PaypalPayment
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return PaypalPayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): PaypalPayment
    {
        return $this->paymentFactory->createPaypalPayment(
            $data['uuid'],
            $data['type'],
            $data['amount']
        );
    }
}
