<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Exception\Validator;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;
use Throwable;

class MaximumStockException extends Exception implements ValidatorException
{
    public function __construct(
        int $stock,
        int $maxStock,
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(
                'Stock quantity %1$d exceeding the maximum quantity (%2$d).',
                $stock,
                $maxStock
            ),
            $code,
            $previous
        );
    }

    public function errorCodes(): array
    {
        return [ValidationErrorCodes::TOO_BIG_STOCK];
    }
}
