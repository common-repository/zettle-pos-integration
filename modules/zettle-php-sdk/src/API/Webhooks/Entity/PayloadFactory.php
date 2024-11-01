<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\API\Webhooks\Entity;

interface PayloadFactory
{

    public function fromArray(array $data): Payload;
}
