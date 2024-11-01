<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding\Provider;

use Inpsyde\Zettle\Onboarding\Listener\UnhandledErrorListener;
use Inpsyde\Zettle\Provider;
use Psr\Container\ContainerInterface as C;

class ErrorListenerProvider implements Provider
{
    /**
     * @var UnhandledErrorListener
     */
    private $unhandledErrorListener;

    public function __construct(
        UnhandledErrorListener $unhandledErrorListener
    ) {
        $this->unhandledErrorListener = $unhandledErrorListener;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_action(
            'inpsyde.zettle.settings.output-failed',
            $this->unhandledErrorListener
        );

        return true;
    }
}
