<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Exception\Validator\Presentation;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;
use Throwable;

class InvalidHexColorException extends Exception implements ValidatorException
{
    public function __construct(string $color, Throwable $previous = null)
    {
        parent::__construct(
            "Presentation contains invalid hex color {$color}",
            0,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::INVALID_HEX_COLOR];
    }
}
