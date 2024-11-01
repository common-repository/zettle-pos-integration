<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\InvoicePayment;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\PaymentFactory;
use Inpsyde\Zettle\PhpSdk\DAL\Exception\EntityFactoryException;

class InvoicePaymentBuilder implements InvoicePaymentBuilderInterface
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
    public function createDataArray(InvoicePayment $invoicePayment): array
    {
        return [
            'uuid' => (string) $invoicePayment->uuid(),
            'amount' => $invoicePayment->amount(),
            'type' => $invoicePayment->type()->getValue(),
            'attributes' => [
                'orderUuid' => (string) $invoicePayment->orderUuid(),
                'invoiceNumber' => $invoicePayment->invoiceNumber(),
                'dueDate' => $invoicePayment->dueDate()->format('Y-m-d'),
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): InvoicePayment
    {
        return $this->build($data);
    }

    /**
     * @param array $data
     *
     * @return InvoicePayment
     *
     * @throws EntityFactoryException
     */
    private function build(array $data): InvoicePayment
    {
        return $this->paymentFactory->createInvoicePayment(
            $data['uuid'],
            $data['type'],
            $data['amount'],
            $data['attributes']['orderUUID'],
            $data['attributes']['invoiceNr'],
            $data['attributes']['dueDate']
        );
    }
}
