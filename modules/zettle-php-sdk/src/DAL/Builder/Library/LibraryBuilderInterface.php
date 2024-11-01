<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Library;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Library\Library;

interface LibraryBuilderInterface
{
    /**
     * @param array $data
     *
     * @return Library
     */
    public function buildFromArray(array $data): Library;

    /**
     * @param Library $library
     *
     * @return array
     */
    public function createDataArray(Library $library): array;
}
