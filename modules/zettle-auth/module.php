<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Auth;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new AuthModule();
};
