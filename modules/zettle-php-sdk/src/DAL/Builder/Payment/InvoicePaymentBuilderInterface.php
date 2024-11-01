<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\InvoicePayment;

interface InvoicePaymentBuilderInterface
{
    /**
     * @param array $data
     *
     * @return InvoicePayment
     */
    public function buildFromArray(array $data): InvoicePayment;

    /**
     * @param InvoicePayment $invoicePayment
     *
     * @return array
     */
    public function createDataArray(InvoicePayment $invoicePayment): array;
}
