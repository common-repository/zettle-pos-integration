<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\ProductDebug;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new ProductDebugModule();
};
