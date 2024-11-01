<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Map;

use Inpsyde\Zettle\PhpSdk\Exception\IdNotFoundException;

interface LocalIdProvider
{

    /**
     * @param string $remoteId
     * @throws IdNotFoundException
     * @return int
     */
    public function localId(string $remoteId): int;
}
