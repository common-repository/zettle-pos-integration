<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Exception;

interface ValidatorException extends BuilderException
{
    /**
     * Values of ValidationErrorCodes
     * @return string[]
     */
    public function errorCodes(): array;
}
