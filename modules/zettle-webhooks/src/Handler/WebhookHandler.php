<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Webhooks\Handler;

use Inpsyde\Zettle\PhpSdk\API\Webhooks\Entity\Payload;

interface WebhookHandler
{

    /**
     * @param Payload $payload
     *
     * @return bool
     */
    public function accepts(Payload $payload): bool;

    /**
     * @param Payload $payload
     *
     * @return mixed
     */
    public function handle(Payload $payload);
}
