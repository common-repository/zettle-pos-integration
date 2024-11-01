<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Exception\Validator\VariantOptionDefinitions;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;
use Throwable;

class VariantOptionAmountMismatchException extends Exception implements ValidatorException
{

    public function __construct(int $expected, int $amount, Throwable $previous = null)
    {
        parent::__construct(
            "Expected {$expected} VariantOptions but found {$amount}",
            0,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::VARIANT_OPTION_AMOUNT_MISMATCH];
    }
}
