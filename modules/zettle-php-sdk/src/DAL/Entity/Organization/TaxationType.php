<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Organization;

interface TaxationType
{
    public const NONE = 'NONE';

    public const VAT = 'VAT';

    public const SALES_TAX = 'SALES_TAX';
}
