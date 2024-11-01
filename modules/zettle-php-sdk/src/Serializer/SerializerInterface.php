<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Serializer;

interface SerializerInterface
{

    /**
     * @param $entity
     * @param SerializerInterface|null $serializer
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     *
     * @return array
     */
    public function serialize($entity, ?SerializerInterface $serializer = null): array;
}
