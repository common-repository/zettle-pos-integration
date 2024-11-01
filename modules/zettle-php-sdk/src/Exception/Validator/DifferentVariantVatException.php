<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Exception\Validator;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;
use Throwable;

final class DifferentVariantVatException extends Exception implements ValidatorException
{

    public function __construct(
        string $productName,
        array $vatValues,
        int $code = 0,
        Throwable $previous = null
    ) {
        $vatStr = implode(', ', $vatValues);

        parent::__construct(
            "The Product {$productName} has variant with different VAT values: [{$vatStr}].",
            $code,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::DIFFERING_VARIANT_TAXES];
    }
}
