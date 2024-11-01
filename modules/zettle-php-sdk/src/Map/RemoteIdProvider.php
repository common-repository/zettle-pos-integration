<?php

namespace Inpsyde\Zettle\PhpSdk\Map;

use Inpsyde\Zettle\PhpSdk\Exception\IdNotFoundException;

interface RemoteIdProvider
{

    /**
     * @param int $localId
     *
     * @return string
     * @throws IdNotFoundException
     */
    public function remoteId(int $localId): string;
}
