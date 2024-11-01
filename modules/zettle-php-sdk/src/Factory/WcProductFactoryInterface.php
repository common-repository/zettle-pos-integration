<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Factory;

use WC_Product;

interface WcProductFactoryInterface
{
    /**
     * Fetch a Record with the provided uuid and return a WC_Product
     *
     * @param string $uuid
     *
     * @return WC_Product|null
     */
    public function fromUuid(string $uuid): ?WC_Product;
}
