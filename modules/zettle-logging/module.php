<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Logging;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new ZettleLoggingModule();
};
