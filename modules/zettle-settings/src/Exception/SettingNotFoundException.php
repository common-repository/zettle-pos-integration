<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Settings\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class SettingNotFoundException extends Exception implements NotFoundExceptionInterface
{

}
