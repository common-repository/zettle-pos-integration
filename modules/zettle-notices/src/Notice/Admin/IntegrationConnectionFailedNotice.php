<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Notices\Notice\Admin;

use Inpsyde\Zettle\Notices\Notice\NoticeInterface;
use Inpsyde\Zettle\Onboarding\OnboardingState;

class IntegrationConnectionFailedNotice implements NoticeInterface
{
    /**
     * @var callable
     */
    private $isIntegrationPageCallback;

    /**
     * @var callable
     */
    private $authCheckCallback;

    /**
     * @var bool
     */
    private $isSavingSettings;

    /**
     * @var string
     */
    private $apiCreationLink;

    public function __construct(
        callable $isIntegrationPageCallback,
        callable $authCheckCallback,
        bool $isSavingSettings,
        string $apiCreationLink
    ) {

        $this->isIntegrationPageCallback = $isIntegrationPageCallback;
        $this->authCheckCallback = $authCheckCallback;
        $this->isSavingSettings = $isSavingSettings;
        $this->apiCreationLink = $apiCreationLink;
    }

    /**
     * @inheritDoc
     */
    public function accepts(string $currentState): bool
    {
        if ($currentState !== OnboardingState::ONBOARDING_COMPLETED) {
            return false;
        }

        if (!($this->isIntegrationPageCallback)()) {
            return false;
        }

        // admin_notices fires before WC settings save handling
        // and WC also does not redirect to GET after saving settings.
        // So if we check auth in this request, we may use old API key.
        if ($this->isSavingSettings) {
            return false;
        }

        if (($this->authCheckCallback)()) {
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

        <div class="notice notice-error zettle" style="padding: 1.2rem 1rem">
            <h4 style="margin-top: 0">
                <?php esc_html_e(
                    'PayPal Zettle POS - Unable to connect to PayPal Zettle',
                    'zettle-pos-integration'
                ); ?>
            </h4>

            <p>
                <?php esc_html_e(
                    'Create a new API key at PayPal Zettle and paste it in the field below to connect again.',
                    'zettle-pos-integration'
                ); ?>
            </p>

            <p style="padding-bottom: 1rem;">
                <?php esc_html_e(
                    'If the connection problem still occur, please disconnect this integration and reconnect again.',
                    'zettle-pos-integration'
                ); ?>
            </p>

            <a class="button button-secondary" href="<?php echo esc_url($this->apiCreationLink) ?>">
                <?php esc_html_e('Create PayPal Zettle API key', 'zettle-pos-integration') ?>
            </a>
        </div>

        <?php return ob_get_clean();
    }
}
