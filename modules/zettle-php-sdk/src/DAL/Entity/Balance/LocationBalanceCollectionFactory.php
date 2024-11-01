<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Balance;

class LocationBalanceCollectionFactory
{
    /**
     * @return LocationBalanceCollection
     */
    public function create(): LocationBalanceCollection
    {
        return new LocationBalanceCollection();
    }
}
