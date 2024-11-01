<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Webhooks;

return static function (): WebhookModule {
    return new WebhookModule();
};
