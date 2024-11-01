<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Vat;

interface VatSetterInterface
{
    public function setVat(?Vat $vat): void;
}
