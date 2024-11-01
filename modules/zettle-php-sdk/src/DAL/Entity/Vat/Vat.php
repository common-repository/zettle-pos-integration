<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Vat;

final class Vat
{
    /**
     * @var float
     */
    private $percentage;

    /**
     * Vat constructor.
     * @param float $percentage
     */
    public function __construct(float $percentage)
    {
        $this->percentage = $percentage;
    }

    /**
     * @return float
     */
    public function percentage(): float
    {
        return $this->percentage;
    }
}
