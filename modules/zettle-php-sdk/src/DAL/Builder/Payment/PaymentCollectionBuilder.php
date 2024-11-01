<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\PaymentCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\PaymentCollectionFactory;

class PaymentCollectionBuilder implements PaymentCollectionBuilderInterface
{
    /**
     * @var PaymentCollectionFactory
     */
    private $paymentCollectionFactory;

    /**
     * @var PaymentBuilderInterface
     */
    private $paymentBuilder;

    /**
     * PaymentCollectionBuilder constructor.
     *
     * @param PaymentCollectionFactory $paymentCollectionFactory
     * @param PaymentBuilderInterface $paymentBuilder
     */
    public function __construct(
        PaymentCollectionFactory $paymentCollectionFactory,
        PaymentBuilderInterface $paymentBuilder
    ) {
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->paymentBuilder = $paymentBuilder;
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(PaymentCollection $paymentCollection): array
    {
        $data = [];

        foreach ($paymentCollection->all() as $payment) {
            $data[][] = $this->paymentBuilder->createDataArray($payment);
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): PaymentCollection
    {
        $paymentCollection = $this->paymentCollectionFactory->create();

        foreach ($data as $payment) {
            $paymentCollection->add(
                $this->paymentBuilder->buildFromArray($payment)
            );
        }

        return $paymentCollection;
    }
}
