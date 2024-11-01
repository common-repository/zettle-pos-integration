<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\API\Webhooks\Entity;

use Inpsyde\Zettle\PhpSdk\Exception\WebhookException;

interface WebhookFactory
{

    /**
     * @param array $data
     *
     * @return Webhook
     *
     * @throws WebhookException
     */
    public function fromArray(array $data): Webhook;
}
