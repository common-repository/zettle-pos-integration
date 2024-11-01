<?php

declare(strict_types=1);

// phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong

namespace Inpsyde\Zettle\Onboarding\Settings\View;

use Inpsyde\Zettle\Onboarding\Settings\ButtonAction;
use WC_Settings_API;

class ApiCredentialsView extends SettingsFieldView implements OnboardingView
{
    /**
     * @var array
     */
    private $apiKeyCreationLink;

    /**
     * ApiCredentialsView constructor.
     *
     * @param WC_Settings_API $settingsApi
     * @param array $apiKeyCreationLink
     * @param string $title
     * @param string $content
     * @param array $fields
     * @param string $notice
     * @param array $allowedFieldTags
     */
    public function __construct(
        WC_Settings_API $settingsApi,
        array $apiKeyCreationLink,
        string $title,
        string $content,
        array $fields,
        string $notice = '',
        array $allowedFieldTags = []
    ) {
        $this->apiKeyCreationLink = $apiKeyCreationLink;

        parent::__construct(
            $settingsApi,
            $title,
            $content,
            $fields,
            $notice,
            $allowedFieldTags
        );
    }

    public function renderHeader(): string
    {
        ob_start(); ?>

        <h2>
            <?php echo esc_html($this->title); ?>
        </h2>

        <?php return ob_get_clean();
    }

    public function renderContent(): string
    {
        ob_start();

        if (!empty($this->notice)) : ?>
            <p>
                <strong><?php echo esc_html($this->notice); ?></strong>
            </p>
        <?php endif; ?>

        <p>
            <?php echo sprintf(
                "%s %s %s",
                esc_html__('1.', 'zettle-pos-integration'),
                sprintf(
                    '<a class="link" rel="noopener noreferrer" target="_blank"
                        href="%s" data-popup="%s">%s</a>',
                    esc_url($this->apiKeyCreationLink['url']),
                    esc_attr($this->apiKeyCreationLink['popup'] ? 'true' : 'false'),
                    esc_html__('Create an API key', 'zettle-pos-integration')
                ),
                esc_html__('in PayPal Zettle to allow WooCommerce access.', 'zettle-pos-integration')
            ); ?>
        </p>

        <p>
            <?php esc_html_e(
                '2. Paste the key in the field below.',
                'zettle-pos-integration'
            ); ?>
        </p>

        <div class="zettle-settings-onboarding-fields" style="margin-top: 1.5rem">
            <?php foreach ($this->fields as $fieldId) {
                if (isset($this->settingsApi->form_fields[$fieldId])) {
                    $fieldConfig = $this->settingsApi->form_fields[$fieldId];

                    unset($fieldConfig['zettle_hide']);

                    $fieldConfig['custom_attributes'] = $this->filterCustomAttributes(
                        $fieldConfig['custom_attributes']
                    );

                    $field = $this->settingsApi->generate_settings_html(
                        [$fieldId => $fieldConfig],
                        false
                    );

                    $strippedField = strip_tags(
                        $field,
                        sprintf(
                            '<%s>',
                            implode('><', $this->allowedFieldTags)
                        )
                    );

                    echo '<div class="zettle-api-key field-row">';
                    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $strippedField;
                    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo '</div>';
                }
            } ?>
        </div>

        <?php return ob_get_clean();
    }

    public function renderProceedButton(): string
    {
        return $this->renderActionButton(
            ButtonAction::PROCEED,
            __('Authenticate with PayPal Zettle', 'zettle-pos-integration')
        );
    }

    /**
     * @param array $customAttributes
     *
     * @return array
     */
    private function filterCustomAttributes(array $customAttributes): array
    {
        $allowedList = [
            'autocomplete',
            'required',
        ];

        foreach ($customAttributes as $customAttribute => $value) {
            if (!in_array($customAttribute, $allowedList, true)) {
                unset($customAttributes[$customAttribute]);
            }
        }

        return $customAttributes;
    }
}
