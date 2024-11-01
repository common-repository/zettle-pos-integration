<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Settings;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Inpsyde\Zettle\BootableProviderAwareTrait;
use Inpsyde\Zettle\BootableProviderModuleInterface;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

class SettingsModule implements ModuleInterface, BootableProviderModuleInterface
{
    use BootableProviderAwareTrait;

    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(
            require __DIR__ . '/../services.php',
            require __DIR__ . '/../extensions.php'
        );
    }

    /**
     * @inheritDoc
     */
    public function run(ContainerInterface $container): void
    {
        if (!is_admin()) {
            return;
        }

        $this->bootProviders(
            $container,
            ...$container->get('zettle.settings.provider')
        );
    }
}
