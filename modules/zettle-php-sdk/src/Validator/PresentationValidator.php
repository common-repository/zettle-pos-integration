<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Validator;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Presentation\Presentation;
use Inpsyde\Zettle\PhpSdk\Exception\Validator\Presentation\InvalidHexColorException;
use Inpsyde\Zettle\PhpSdk\Exception\Validator\Presentation\ShortHexColorException;
use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;

/**
 * Class PresentationValidator
 *
 * Verifies that a Presentation's color values are 6-digit hex strings
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
 *
 * @package Inpsyde\Zettle\PhpSdk\Validator
 */
class PresentationValidator implements ValidatorInterface
{

    public const HEX_COLOR_LENGTH = 6;

    /**
     * @inheritDoc
     */
    public function accepts($entity): bool
    {
        return $entity instanceof Presentation
            && ($entity->textColor() !== null && $entity->backgroundColor() !== null);
    }

    /**
     * @inheritDoc
     */
    public function validate($entity): bool
    {
        assert($entity instanceof Presentation);

        $this->assertValidLongHexValue($entity->backgroundColor());
        $this->assertValidLongHexValue($entity->textColor());

        return true;
    }

    /**
     * @param string $string
     *
     * @throws ValidatorException
     */
    private function assertValidLongHexValue(string $string)
    {
        $color = ltrim($string, '#');

        if (function_exists('ctype_xdigit') && !ctype_xdigit($color)) {
            throw new InvalidHexColorException($color);
        }

        if (strlen($color) < self::HEX_COLOR_LENGTH) {
            throw new ShortHexColorException($color, 'Presentation');
        }
    }
}
