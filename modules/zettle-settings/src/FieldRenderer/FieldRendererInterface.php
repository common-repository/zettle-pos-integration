<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Settings\FieldRenderer;

use WC_Settings_API;

interface FieldRendererInterface
{

    public function accepts(string $fieldId, array $fieldConfig, WC_Settings_API $settingsApi): bool;

    public function render(string $fieldId, array $fieldConfig, WC_Settings_API $settingsApi): string;
}
