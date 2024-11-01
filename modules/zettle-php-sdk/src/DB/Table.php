<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DB;

/**
 * Interface Table
 */
interface Table
{
    const MAX_INDEX_LENGTH = 191;

    /**
     * The name of the database table
     *
     * @return string
     */
    public function name(): string;

    /**
     * The table schema
     *
     * @return string
     */
    public function schema(): string;
}
