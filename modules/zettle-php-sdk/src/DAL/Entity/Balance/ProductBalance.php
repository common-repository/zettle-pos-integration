<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Balance;

class ProductBalance
{
    /**
     * @var string
     */
    private $locationUuid;

    /**
     * @var LocationBalanceCollection
     */
    private $variants;

    /**
     * ProductBalance constructor.
     * @param string $locationUuid
     * @param LocationBalanceCollection $variants
     */
    public function __construct(
        string $locationUuid,
        LocationBalanceCollection $variants
    ) {

        $this->locationUuid = $locationUuid;
        $this->variants = $variants;
    }

    /**
     * @return string
     */
    public function locationUuid(): string
    {
        return $this->locationUuid;
    }

    /**
     * @return LocationBalanceCollection
     */
    public function variants(): LocationBalanceCollection
    {
        return $this->variants;
    }
}
