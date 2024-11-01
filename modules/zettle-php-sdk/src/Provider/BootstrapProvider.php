<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Provider;

use Inpsyde\Zettle\PhpSdk\Bootstrap;
use Inpsyde\Zettle\Provider;
use Psr\Container\ContainerInterface as C;

class BootstrapProvider implements Provider
{

    /**
     * @var Bootstrap
     */
    private $boostrap;

    /**
     * BootstrapProvider constructor.
     *
     * @param Bootstrap $boostrap
     */
    public function __construct(Bootstrap $boostrap)
    {
        $this->boostrap = $boostrap;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_action(
            'zettle-pos-integration.migrate',
            function () {
                $this->boostrap->activate();
            }
        );
        add_action(
            'zettle-pos-integration.activate',
            function () {
                $this->boostrap->activate();
            },
            5
        );

        return true;
    }
}
