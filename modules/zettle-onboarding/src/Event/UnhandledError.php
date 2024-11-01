<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding\Event;

use Inpsyde\StateMachine\Event\GenericStateChange;
use Throwable;

class UnhandledError extends GenericStateChange
{
    /**
     * @var Throwable
     */
    private $exception;

    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }
}
