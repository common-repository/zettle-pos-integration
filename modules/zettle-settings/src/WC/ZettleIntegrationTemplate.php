<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Settings\WC;

interface ZettleIntegrationTemplate
{
    /**
     * @return string
     */
    public function render(): string;
}
