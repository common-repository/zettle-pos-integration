<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Exception\Validator\VariantOptionDefinitions;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;
use Throwable;

class EmptyVariantOptionDefinitionsException extends Exception implements ValidatorException
{
    public function __construct(Throwable $previous = null)
    {
        parent::__construct(
            "Given Product requires VariantOptionDefinitions, but doesn't contains Definitions.",
            0,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::NO_VARIANT_OPTIONS];
    }
}
