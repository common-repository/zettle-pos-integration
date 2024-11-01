<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Handler\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment\InvoicePaymentBuilder;
use Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment\InvoicePaymentBuilderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;

class InvoicePaymentHandler extends AbstractPaymentHandler
{
    /**
     * @var InvoicePaymentBuilder
     */
    private $invoicePaymentBuilder;

    /**
     * InvoicePaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param InvoicePaymentBuilderInterface $invoicePaymentBuilder
     */
    public function __construct(
        string $validPaymentType,
        InvoicePaymentBuilderInterface $invoicePaymentBuilder
    ) {
        parent::__construct($validPaymentType);
        $this->invoicePaymentBuilder = $invoicePaymentBuilder;
    }

    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->invoicePaymentBuilder->createDataArray($payment);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->invoicePaymentBuilder->buildFromArray($data);
    }
}
