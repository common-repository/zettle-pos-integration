<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding\Settings;

interface ButtonAction
{
    public const PROCEED = 'save';
    public const BACK = 'back';
    public const CANCEL = 'cancel';
    public const DELETE = 'delete';
}
