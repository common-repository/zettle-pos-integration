<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Category;

use Inpsyde\Zettle\PhpSdk\DAL\Builder\BuilderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Category\CategoryCollection;

interface CategoryCollectionBuilderInterface extends BuilderInterface
{
    /**
     * @param array $data
     *
     * @return CategoryCollection
     */
    public function buildFromArray(array $data): CategoryCollection;

    /**
     * @param CategoryCollection $categoryCollection
     *
     * @return array
     */
    public function createDataArray(CategoryCollection $categoryCollection): array;
}
