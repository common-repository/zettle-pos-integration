<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Validator;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use Inpsyde\Zettle\PhpSdk\Exception\Validator\VariantOption\MaximumVariantOptionNameCharacterLengthException;
use Inpsyde\Zettle\PhpSdk\Exception\Validator\VariantOption\MinimumVariantOptionNameCharacterLengthException;

// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType

class VariantOptionValidator implements ValidatorInterface
{

    public const MIN_NAME_LENGTH = 1;
    public const MAX_NAME_LENGTH = 30;

    /**
     * @inheritDoc
     */
    public function accepts($entity): bool
    {
        return $entity instanceof VariantOption;
    }

    /**
     * @inheritDoc
     */
    public function validate($entity): bool
    {
        assert($entity instanceof VariantOption);

        $name = $entity->name();
        /** @psalm-suppress RedundantCast */
        $nameLength = (int) mb_strlen($name);

        if ($nameLength < self::MIN_NAME_LENGTH) {
            throw new MinimumVariantOptionNameCharacterLengthException($name, self::MIN_NAME_LENGTH);
        }

        if ($nameLength > self::MAX_NAME_LENGTH) {
            throw new MaximumVariantOptionNameCharacterLengthException($name, self::MAX_NAME_LENGTH);
        }

        return true;
    }
}
