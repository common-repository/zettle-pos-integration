<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Exception\Validator\VariantOption;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;
use Throwable;

class MinimumVariantOptionNameCharacterLengthException extends Exception implements ValidatorException
{
    public function __construct(string $variantOptionName, int $minLength, Throwable $previous = null)
    {
        parent::__construct(
            "The given VariantOption {$variantOptionName} is too short,
            should be at least {$minLength} character long.",
            0,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::TOO_SHORT_VARIANT_NAME];
    }
}
