<?php

namespace Inpsyde\Zettle\PhpSdk\API\Webhooks\Entity;

interface RegisteredWebhook extends Webhook
{

    public function status(): string;

    public function signingKey(): string;
}
