<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding\Settings\View;

class WelcomeView implements OnboardingView
{
    /**
     * @var array
     */
    protected $zettleLink;

    /**
     * @param array $zettleLink
     */
    public function __construct(array $zettleLink)
    {
        $this->zettleLink = $zettleLink;
    }

    public function renderHeader(): string
    {
        ob_start(); ?>

        <h2>
            <?php esc_html_e(
                'Grow your business with PayPal Zettle and WooCommerce',
                'zettle-pos-integration'
            ) ?>
        </h2>

        <?php return ob_get_clean();
    }

    public function renderContent(): string
    {
        ob_start() ?>

        <p>
            <?php esc_html_e(
                'The PayPal Zettle point-of-sale system allows you to take cash, card, contactless payments and more.
                 Connect WooCommerce with PayPal Zettle to keep products and inventory in sync – all in one place.',
                'zettle-pos-integration'
            ) ?>
        </p>

        <p>
            <?php esc_html_e(
                'Sync your WooCommerce products and inventory to PayPal Zettle in a few clicks.
                Make a sale on either platform and your inventory will update instantly.
                Keep your products up-to-date by managing them solely in WooCommerce,
                so you can focus on selling.',
                'zettle-pos-integration'
            ) ?>
        </p>

        <p>
            <?php esc_html_e(
                'To see which markets PayPal Zettle is available in, please visit ',
                'zettle-pos-integration'
            ); ?>

            <a class="link"
               rel="noopener noreferrer"
               target="_blank"
               href="<?php echo esc_url_raw($this->zettleLink['url']); ?>">
                <?php echo esc_html($this->zettleLink['title']); ?></a>.
        </p>

        <div class="zettle-settings-onboarding-content-get-started">
            <h3><?php esc_html_e('How to get started', 'zettle-pos-integration') ?></h3>

            <?php echo wp_kses_post($this->renderGetStartedContent()); ?>
        </div>

        <?php return ob_get_clean();
    }

    public function renderGetStartedContent(): string
    {
        $imgResources = sprintf(
            '%s/zettle-assets/resources/img',
            plugin_dir_url(dirname(__DIR__, 4) . '/zettle-pos-integration.php')
        );

        ob_start() ?>

        <div class="zettle-settings-onboarding-content-get-started-container columns-3">
            <div class="column">
                <img src="<?php echo esc_url_raw("{$imgResources}/connect.jpg") ?>"
                     alt="<?php esc_attr_e('Connect in minutes', 'zettle-pos-integration') ?>"
                     title="<?php esc_attr_e('Connect in minutes', 'zettle-pos-integration') ?>">

                <h4><?php esc_html_e('Connect in minutes', 'zettle-pos-integration') ?></h4>
                <p>
                    <?php esc_html_e(
                        'Connect your accounts, sync your library to PayPal Zettle and start selling.',
                        'zettle-pos-integration'
                    ); ?>
                </p>
            </div>
            <div class="column">
                <img src="<?php echo esc_url_raw("{$imgResources}/zettle.jpg") ?>"
                     alt="<?php esc_attr_e('Manage products in one place', 'zettle-pos-integration') ?>"
                     title="<?php esc_attr_e('Manage products in one place', 'zettle-pos-integration') ?>">
                <h4><?php esc_html_e('Manage products in one place', 'zettle-pos-integration') ?></h4>
                <p>
                    <?php esc_html_e(
                        'Products sync from WooCommerce to PayPal Zettle automatically.',
                        'zettle-pos-integration'
                    ); ?>
                </p>
            </div>
            <div class="column">
                <img src="<?php echo esc_url_raw("{$imgResources}/sync.jpg") ?>"
                     alt="<?php esc_attr_e('Sync in real-time', 'zettle-pos-integration') ?>"
                     title="<?php esc_attr_e('Sync in real-time', 'zettle-pos-integration') ?>">
                <h4><?php esc_html_e('Sync in real-time', 'zettle-pos-integration') ?></h4>
                <p>
                    <?php esc_html_e(
                        'Inventory sync both ways when you edit or make a sale.',
                        'zettle-pos-integration'
                    ); ?>
                </p>
            </div>
        </div>

        <?php return ob_get_clean();
    }

    public function renderProceedButton(): string
    {
        return '';
    }

    public function renderBackButton(): string
    {
        return '';
    }
}
