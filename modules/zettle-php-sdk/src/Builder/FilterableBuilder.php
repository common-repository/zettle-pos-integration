<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Builder;

use Inpsyde\Zettle\PhpSdk\Filter\FilterInterface;

/**
 * Class FilterableBuilder
 * Decorates another Builder and runs its result through a Filter
 *
 * @package Inpsyde\Zettle\PhpSdk\Builder
 */
class FilterableBuilder implements BuilderInterface
{

    /**
     * @var BuilderInterface
     */
    private $builder;

    /**
     * @var FilterInterface
     */
    private $filter;

    public function __construct(BuilderInterface $builder, FilterInterface $filter)
    {
        $this->builder = $builder;
        $this->filter = $filter;
    }

    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null)
    {
        $result = $this->builder->build($className, $payload, $builder ?? $this);
        if (!$this->filter->accepts($result, $payload)) {
            return $result;
        }

        return $this->filter->filter($result, $payload);
    }
}
