<?php

namespace Inpsyde\Zettle\PhpSdk\Map;

use Inpsyde\Zettle\PhpSdk\Exception\IdNotFoundException;

interface MultipleRemoteIdProvider
{

    /**
     * @param int $localId
     *
     * @return string[]
     *
     * @throws IdNotFoundException
     */
    public function remoteIds(int $localId): array;
}
