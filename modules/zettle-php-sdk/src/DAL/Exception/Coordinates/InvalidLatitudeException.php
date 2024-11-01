<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Exception\Coordinates;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;

final class InvalidLatitudeException extends Exception implements ValidatorException
{
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::INVALID_COORDINATES];
    }
}
