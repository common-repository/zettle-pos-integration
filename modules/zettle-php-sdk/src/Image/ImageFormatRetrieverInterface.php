<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Image;

use UnexpectedValueException;

interface ImageFormatRetrieverInterface
{
    /**
     * Determines the format of the given image.
     * @param string $url
     * @return string One of ImageFormat values
     * @throws UnexpectedValueException If failed to determine type.
     */
    public function determineImageFormat(string $url): string;
}
