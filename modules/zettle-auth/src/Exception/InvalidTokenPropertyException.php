<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Auth\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class InvalidTokenPropertyException extends Exception implements NotFoundExceptionInterface
{

}
