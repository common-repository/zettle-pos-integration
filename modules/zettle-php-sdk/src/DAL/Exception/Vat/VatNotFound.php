<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Exception\Vat;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;

final class VatNotFound extends Exception implements ValidatorException
{
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::TAX_RATE_NOT_FOUND];
    }
}
