<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Exception\Validator;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;
use Throwable;

final class MinimumVariantsException extends Exception implements ValidatorException
{
    public function __construct(string $productName, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            "The Product should have at least one variant: {$productName}",
            $code,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::NO_VARIANTS];
    }
}
