<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Sync;

interface PriceSyncMode
{
    public const ENABLED = 'gross';

    public const DISABLED = 'zero';
}
