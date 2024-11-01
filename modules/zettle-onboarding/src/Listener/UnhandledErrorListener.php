<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding\Listener;

use Inpsyde\StateMachine\Exceptions\DenyTransitionException;
use Inpsyde\StateMachine\StateMachineInterface;
use Inpsyde\Zettle\Auth\Exception\AuthenticationException;
use Inpsyde\Zettle\Http\PageReloaderInterface;
use Inpsyde\Zettle\Onboarding\Event\AuthFailed;
use Inpsyde\Zettle\Onboarding\Event\UnhandledError;
use Inpsyde\Zettle\PhpSdk\Exception\ZettleRestException;
use Throwable;

class UnhandledErrorListener
{
    /**
     * @var StateMachineInterface
     */
    private $stateMachine;

    /**
     * @var PageReloaderInterface
     */
    private $pageReloader;

    /**
     * @var bool
     */
    private $isDebugMode;

    /**
     * @param StateMachineInterface $stateMachine
     * @param PageReloaderInterface $pageReloader
     * @param bool $isDebugMode
     */
    public function __construct(
        StateMachineInterface $stateMachine,
        PageReloaderInterface $pageReloader,
        bool $isDebugMode
    ) {

        $this->stateMachine = $stateMachine;
        $this->pageReloader = $pageReloader;
        $this->isDebugMode = $isDebugMode;
    }

    /**
     * @param Throwable $error
     * @throws DenyTransitionException
     * @throws Throwable if in debug mode
     */
    public function __invoke(Throwable $error)
    {
        if ($this->isDebugMode) {
            throw $error;
        }

        $currentState = $this->stateMachine->currentState()->name();

        if ($this->isAuthError($error)) {
            $this->stateMachine->handle(new AuthFailed());

            if ($this->stateMachine->currentState()->name() !== $currentState) {
                $this->pageReloader->reload();
                return;
            }
        }

        $this->stateMachine->handle(new UnhandledError($error));

        if ($this->stateMachine->currentState()->name() !== $currentState) {
            $this->pageReloader->reload();
        }
    }

    /**
     * Walks through the exception stack to find a known authentication error
     * @param Throwable $error
     *
     * @return bool
     */
    private function isAuthError(Throwable $error): bool
    {
        $currentError = $error;

        while ($currentError !== null) {
            if (
                $currentError instanceof ZettleRestException
                && $currentError->isType(ZettleRestException::TYPE_UNAUTHENTICATED)
            ) {
                return true;
            }

            if ($currentError instanceof AuthenticationException) {
                return true;
            }

            $currentError = $currentError->getPrevious();
        }

        return false;
    }
}
