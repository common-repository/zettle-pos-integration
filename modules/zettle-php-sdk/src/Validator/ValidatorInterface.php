<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Validator;

// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType

use Inpsyde\Zettle\PhpSdk\Exception\ValidatorException;

interface ValidatorInterface
{

    /**
     * @param $entity
     *
     * @return bool
     */
    public function accepts($entity): bool;

    /**
     * @param $entity
     *
     * @return bool
     *
     * @throws ValidatorException
     */
    public function validate($entity): bool;
}
