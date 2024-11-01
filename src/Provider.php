<?php

declare(strict_types=1);

namespace Inpsyde\Zettle;

use Psr\Container\ContainerInterface as C;

/**
 * Interface for bootable Provider
 */
interface Provider
{

    /**
     * @param C $container
     *
     * @return bool
     */
    public function boot(C $container): bool;
}
