<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Http;

interface PageReloaderInterface
{
    /**
     * Reload the current URL.
     */
    public function reload(): void;
}
