<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Exception\Validator\VariantOption;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;
use Throwable;

class MaximumVariantOptionNameCharacterLengthException extends Exception implements ValidatorException
{
    public function __construct(string $variantOptionName, int $maxLength, Throwable $previous = null)
    {
        parent::__construct(
            "The given VariantOption {$variantOptionName} is too long,
            should be maximum {$maxLength} characters long",
            0,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::TOO_LONG_VARIANT_NAME];
    }
}
