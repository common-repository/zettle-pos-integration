<?php

namespace Inpsyde\Zettle\PhpSdk\Map;

interface RecordMetaProvider
{

    public function metaData(int $localId, string $remoteId): array;
}
