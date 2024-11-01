<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding\Provider;

use Inpsyde\StateMachine\StateMachineInterface;
use Inpsyde\Zettle\Onboarding\Event\AuthCheck;
use Inpsyde\Zettle\Provider;
use Psr\Container\ContainerInterface as C;

class OnboardingRenderProvider implements Provider
{

    /**
     * @var StateMachineInterface
     */
    private $stateMachine;

    public function __construct(StateMachineInterface $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        // when in onboarding, check if auth still successful
        add_action('inpsyde.zettle.onboarding.rendering-started', function (): void {
            $this->stateMachine->handle(new AuthCheck());
        });

        return true;
    }
}
