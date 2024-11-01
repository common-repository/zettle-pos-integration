<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity;

trait PropertyChangeAwareTrait
{
    /**
     * @var array
     */
    protected $changedProperties = [];

    /**
     * @inheritdoc
     */
    public function hasChangedProperties(): bool
    {
        return (!empty($this->changedProperties));
    }

    /**
     * @inheritdoc
     */
    public function allChangedProperties(): array
    {
        return $this->changedProperties;
    }

    /**
     * @param string ...$properties
     */
    protected function addChangedProperties(string ...$properties): void
    {
        foreach ($properties as $property) {
            if (property_exists($this, $property)) {
                $this->changedProperties[] = $property;
            }
        }
    }
}
