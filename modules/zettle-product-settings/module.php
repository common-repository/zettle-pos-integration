<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\ProductSettings;

use Dhii\Modular\Module\ModuleInterface;

return static function (): ModuleInterface {
    return new ProductSettingsModule();
};
