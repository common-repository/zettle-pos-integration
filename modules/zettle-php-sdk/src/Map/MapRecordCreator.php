<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\Map;

use Inpsyde\Zettle\PhpSdk\Exception\IdNotFoundException;

interface MapRecordCreator
{

    /**
     * @param int $localId
     * @param string $remoteId
     * @param array $arguments
     *
     * @return bool
     */
    public function createRecord(int $localId, string $remoteId, array $arguments = []): bool;

    /**
     * @param int $localId
     * @param string $remoteId
     *
     * @return bool
     *
     * @throws IdNotFoundException
     */
    public function deleteRecord(int $localId, string $remoteId): bool;
}
