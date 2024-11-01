<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Notices\Notice\Admin;

use Inpsyde\Zettle\Notices\Notice\NoticeInterface;
use Inpsyde\Zettle\Onboarding\OnboardingState;

class CompleteOnboardingNotice implements NoticeInterface
{
    /**
     * @var callable
     */
    private $isIntegrationPageCallback;

    /**
     * @var string
     */
    private $settingsUrl;

    /**
     * CompleteOnboardingNotice constructor.
     *
     * @param callable $isIntegrationPageCallback
     * @param string $settingsUrl
     */
    public function __construct(
        callable $isIntegrationPageCallback,
        string $settingsUrl
    ) {
        $this->isIntegrationPageCallback = $isIntegrationPageCallback;
        $this->settingsUrl = $settingsUrl;
    }

    /**
     * @inheritDoc
     */
    public function accepts(string $currentState): bool
    {
        if (
            $currentState === OnboardingState::ONBOARDING_COMPLETED
        ) {
            return false;
        }

        if (($this->isIntegrationPageCallback)()) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        ob_start() ?>

        <div class="notice zettle" style="padding: 1.2rem 1rem">
            <h4>
                <?php esc_html_e(
                    'PayPal Zettle POS Configuration',
                    'zettle-pos-integration'
                ); ?>
            </h4>

            <p>
                <?php esc_html_e(
                    'It looks like this is the first time you are using PayPal Zettle POS for WooCommerce.',
                    'zettle-pos-integration'
                ); ?>
            </p>

            <p style="padding-bottom: 1rem;">
                <?php esc_html_e(
                    'Please complete the initial configuration in the integration settings.',
                    'zettle-pos-integration'
                ); ?>
            </p>

            <a class="button button-secondary" href="<?php echo esc_url($this->settingsUrl) ?>">
                <?php esc_html_e('Take me there!', 'zettle-pos-integration') ?>
            </a>
        </div>

        <?php return ob_get_clean();
    }
}
