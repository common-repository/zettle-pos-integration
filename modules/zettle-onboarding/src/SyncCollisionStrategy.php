<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding;

interface SyncCollisionStrategy
{
    public const WIPE = 'wipe';

    public const MERGE = 'merge';
}
