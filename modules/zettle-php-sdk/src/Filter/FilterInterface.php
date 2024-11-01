<?php

declare(strict_types=1);

// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType

namespace Inpsyde\Zettle\PhpSdk\Filter;

interface FilterInterface
{

    /**
     * @param mixed $entity
     * @param mixed $payload
     *
     * @return bool
     */
    public function accepts($entity, $payload): bool;

    /**
     * @param mixed $entity
     * @param mixed $payload
     *
     * @return mixed
     */
    public function filter($entity, $payload);
}
