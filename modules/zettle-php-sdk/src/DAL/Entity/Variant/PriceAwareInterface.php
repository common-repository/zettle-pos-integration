<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Variant;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Price\Price;

interface PriceAwareInterface
{
    public function price(): ?Price;

    public function setPrice(?Price $price): void;
}
