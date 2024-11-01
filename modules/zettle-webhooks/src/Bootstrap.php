<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Webhooks;

use Inpsyde\Zettle\PhpSdk\Exception\WebhookException;
use Inpsyde\Zettle\PhpSdk\Exception\ZettleRestException;
use Inpsyde\Zettle\Webhooks\Job\WebhookRegistrationJob;

class Bootstrap
{
    /**
     * @var callable
     */
    private $createJob;

    /**
     * @var callable
     */
    private $webhookDeletion;

    public function __construct(
        callable $createJob,
        callable $webhookDeletion
    ) {
        $this->createJob = $createJob;
        $this->webhookDeletion = $webhookDeletion;
    }

    public function activate()
    {
        ($this->createJob)(WebhookRegistrationJob::TYPE);
    }

    public function deactivate()
    {
        try {
            ($this->webhookDeletion)();
        } catch (ZettleRestException | WebhookException $exc) {
            // looks like it is already logged inside, so no need to duplicate logging
            // but probably should not throw here to not abort deactivation
        }
    }
}
