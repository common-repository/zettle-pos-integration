<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Notices\Notice\Admin;

use Inpsyde\Zettle\Notices\Notice\NoticeInterface;
use Inpsyde\Zettle\Onboarding\OnboardingState;

class GlobalConnectionFailedNotice implements NoticeInterface
{
    /**
     * @var callable
     */
    private $isIntegrationPageCallback;

    /**
     * @var bool
     */
    private $authFailed;

    /**
     * @var string
     */
    private $settingsUrl;

    public function __construct(
        callable $isIntegrationPageCallback,
        bool $authFailed,
        string $settingsUrl
    ) {

        $this->isIntegrationPageCallback = $isIntegrationPageCallback;
        $this->authFailed = $authFailed;
        $this->settingsUrl = $settingsUrl;
    }

    /**
     * @inheritDoc
     */
    public function accepts(string $currentState): bool
    {
        if ($currentState !== OnboardingState::ONBOARDING_COMPLETED) {
            return false;
        }

        if (($this->isIntegrationPageCallback)()) {
            return false;
        }

        return $this->authFailed;
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        ob_start() ?>

        <div class="notice notice-error zettle" style="padding: 1.2rem 1rem">
            <h4 style="margin-top: 0; margin-bottom: .5rem;">
                <?php esc_html_e(
                    'PayPal Zettle POS - Unable to connect to PayPal Zettle',
                    'zettle-pos-integration'
                ); ?>
            </h4>

            <p style="padding-bottom: .5rem;">
                <?php esc_html_e(
                    'Please visit the PayPal Zettle integration page to update the API key and connect again.',
                    'zettle-pos-integration'
                ); ?>
            </p>

            <a class="button button-secondary" href="<?php echo esc_url($this->settingsUrl) ?>">
                <?php esc_html_e('Go to Integration page', 'zettle-pos-integration') ?>
            </a>
        </div>

        <?php return ob_get_clean();
    }
}
