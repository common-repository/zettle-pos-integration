<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Assets;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new AssetsModule();
};
