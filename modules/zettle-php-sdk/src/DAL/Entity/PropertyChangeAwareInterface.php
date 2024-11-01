<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity;

interface PropertyChangeAwareInterface
{
    /**
     * @return bool
     */
    public function hasChangedProperties(): bool;

    /**
     * @return array
     */
    public function allChangedProperties(): array;
}
