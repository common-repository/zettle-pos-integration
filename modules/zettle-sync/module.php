<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Sync;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new SyncModule();
};
