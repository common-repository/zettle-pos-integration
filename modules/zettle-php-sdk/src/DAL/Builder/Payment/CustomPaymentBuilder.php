<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\CustomPayment;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Inpsyde\Zettle\PhpSdk\DAL\Exception\EntityFactoryException;

class CustomPaymentBuilder implements CustomPaymentBuilderInterface
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
    public function createDataArray(CustomPayment $customPayment): array
    {
        return [
            'uuid' => (string) $customPayment->uuid(),
            'type' => $customPayment->type()->getValue(),
            'amount' => $customPayment->amount(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): CustomPayment
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return CustomPayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): CustomPayment
    {
        return $this->paymentFactory->createCustomPayment(
            $data['uuid'],
            $data['type'],
            $data['amount']
        );
    }
}
