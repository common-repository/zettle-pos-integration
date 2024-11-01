<?php

# -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment;

final class PaymentCollectionFactory
{
    /**
     * @return PaymentCollection
     */
    public function create(): PaymentCollection
    {
        return new PaymentCollection();
    }
}
