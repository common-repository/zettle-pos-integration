<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\StoreCreditPayment;
use Inpsyde\Zettle\PhpSdk\DAL\Exception\EntityFactoryException;

class StoreCreditPaymentBuilder implements StoreCreditPaymentBuilderInterface
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
    public function createDataArray(StoreCreditPayment $storeCreditPayment): array
    {
        return [
            'uuid' => (string) $storeCreditPayment->uuid(),
            'amount' => $storeCreditPayment->amount(),
            'type' => $storeCreditPayment->type()->getValue(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): StoreCreditPayment
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return StoreCreditPayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): StoreCreditPayment
    {
        return $this->paymentFactory->createStoreCreditPayment(
            $data['uuid'],
            $data['type'],
            $data['amount']
        );
    }
}
