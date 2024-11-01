<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Exception\Validator\Image;

use Exception;
use Inpsyde\Zettle\PhpSdk\Exception\ValidationErrorCodes;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;

final class UnsupportedImageFileSizeException extends Exception implements ValidatorException
{
    public function errorCodes(): array
    {
        return [ValidationErrorCodes::UNSUPPORTED_IMAGE_FILE_SIZE];
    }
}
