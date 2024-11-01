<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Exception\Payment;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;

final class InvalidPaymentTypeException extends Exception implements ValidatorException
{
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::INVALID_PAYMENT_TYPE];
    }
}
