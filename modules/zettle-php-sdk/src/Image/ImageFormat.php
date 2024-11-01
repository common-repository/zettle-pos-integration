<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Image;

/**
 * Image formats used in Zettle API
 */
interface ImageFormat
{
    public const JPEG = 'JPEG';
    public const PNG = 'PNG';
    public const TIFF = 'TIFF';
    public const GIF = 'GIF';
    public const BMP = 'BMP';
}
