<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding\Settings\View;

use Inpsyde\Zettle\Onboarding\Settings\ButtonAction;
use Inpsyde\Zettle\Onboarding\Settings\ButtonKind;

class UnhandledErrorView implements OnboardingView
{
    use ButtonRendererTrait;

    public function renderHeader(): string
    {
        ob_start() ?>

        <h2>
            <?php esc_html_e('Critical error', 'zettle-pos-integration') ?>
        </h2>

        <?php return ob_get_clean();
    }

    public function renderContent(): string
    {
        ob_start() ?>

        <p>
            <?php esc_html_e(
                "A critical error occurred.
                Please check the WooCommerce logs for more details
                and press 'Start over' to restart installation.",
                'zettle-pos-integration'
            ); ?>
        </p>

        <?php return ob_get_clean();
    }

    public function renderProceedButton(): string
    {
        return $this->renderActionButton(
            ButtonAction::DELETE,
            __('Start over', 'zettle-pos-integration'),
            // do not make it red because the user does not have any other choice
                ['kind' => ButtonKind::PRIMARY]
        );
    }

    public function renderBackButton(): string
    {
        return '';
    }
}
