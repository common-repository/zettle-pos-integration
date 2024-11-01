<?php

declare(strict_types=1);

// phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong

namespace Inpsyde\Zettle\Onboarding\Settings\View;

use Inpsyde\Zettle\Onboarding\Comparison\StoreComparison;
use Inpsyde\Zettle\Onboarding\DataProvider\Store\StoreDataProvider;
use Inpsyde\Zettle\Onboarding\Settings\ButtonAction;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Tax\TaxRate;
use Inpsyde\Zettle\Sync\PriceSyncMode;

class SyncVatParamView implements OnboardingView
{
    use ButtonRendererTrait;

    /**
     * @var StoreComparison
     */
    private $storeComparison;

    /**
     * @var StoreDataProvider
     */
    private $remoteStoreData;

    /**
     * @var StoreDataProvider
     */
    private $localStoreData;

    /**
     * @var TaxRate[]|null
     */
    private $defaultTaxRates;

    /**
     * @param TaxRate[]|null $defaultTaxRates
     */
    public function __construct(
        StoreComparison $storeComparison,
        StoreDataProvider $remoteStoreData,
        StoreDataProvider $localStoreData,
        ?array $defaultTaxRates
    ) {
        $this->storeComparison = $storeComparison;
        $this->remoteStoreData = $remoteStoreData;
        $this->localStoreData = $localStoreData;
        $this->defaultTaxRates = $defaultTaxRates;
    }

    /**
     * @inheritDoc
     */
    public function renderHeader(): string
    {
        ob_start(); ?>

        <h2>
            <?php esc_html_e('Product prices', 'zettle-pos-integration'); ?>
        </h2>

        <?php if (!$this->storeComparison->priceSyncRequiresTaxSync()) : ?>
            <p>
                <strong>
                    <?php esc_html_e(
                        'Your taxes will not be imported, you may need to configure them manually in your PayPal Zettle account.',
                        'zettle-pos-integration'
                    ); ?>
                </strong>
            </p>
        <?php endif; ?>

        <?php if ($this->defaultTaxRates !== null && empty($this->defaultTaxRates)) : ?>
            <div class="alert alert-warning">
                <?php esc_html_e(
                    'You do not have default tax rates in your PayPal Zettle account, you may want to add them before syncing products.',
                    'zettle-pos-integration'
                ); ?>
            </div>
        <?php endif; ?>

        <?php if (!$this->storeComparison->canSyncPrices()) : ?>
            <p>
                <strong>
                    <?php esc_html_e(
                        'Your prices will not be imported because your settings in PayPal Zettle and WooCommerce do not match:',
                        'zettle-pos-integration'
                    ); ?>
                </strong>
                <?php
                $messages = [];

                if (!$this->storeComparison->currency()) {
                    $messages[] = esc_html(
                        sprintf(
                            /* translators: %1$s, %2$s: Currency codes (EUR, GBP, ...) */
                            __(
                                'Currency: %1$s in PayPal Zettle, %2$s in WooCommerce.',
                                'zettle-pos-integration'
                            ),
                            $this->remoteStoreData->currency(),
                            $this->localStoreData->currency()
                        )
                    );
                }

                if ($this->storeComparison->priceSyncRequiresTaxSync()) {
                    if (!$this->storeComparison->country()) {
                        $messages[] = esc_html(
                            sprintf(
                            /* translators: %1$s, %2$s: Country codes (UK, DE, ...) */
                                __(
                                    'Country: %1$s in PayPal Zettle, %2$s in WooCommerce.',
                                    'zettle-pos-integration'
                                ),
                                $this->remoteStoreData->country(),
                                $this->localStoreData->country()
                            )
                        );
                    }

                    if (!$this->storeComparison->taxesEnabled()) {
                        $messages[] = esc_html(
                            sprintf(
                                __(
                                    'Taxes are disabled in WooCommerce.',
                                    'zettle-pos-integration'
                                )
                            )
                        );
                    } elseif (!$this->storeComparison->taxRatesConfigured()) {
                        $messages[] = esc_html(
                            sprintf(
                                __(
                                    'Tax rates not added in WooCommerce.',
                                    'zettle-pos-integration'
                                )
                            )
                        );
                    }
                }

                echo '<ul>' .
                    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                    implode('', array_map(static function (string $msg): string {
                        return "<li><strong>$msg</strong></li>";
                    }, $messages)) .
                    '</ul>';
                ?>
            </p>
        <?php endif; ?>

        <?php if (!$this->storeComparison->includeTaxes() && $this->storeComparison->canSyncPrices()) : ?>
            <div class="alert alert-warning">
                <?php echo esc_html(
                    sprintf(
                        __(
                            'Remember that your tax inclusion settings in WooCommerce and PayPal Zettle do not match.
                            If you sync prices, the prices will be automatically adjusted to include/exclude taxes and your margins will change.',
                            'zettle-pos-integration'
                        )
                    )
                ); ?>
            </div>
        <?php endif; ?>

        <?php return ob_get_clean();
    }

    /**
     * @inheritDoc
     */
    public function renderContent(): string
    {
        ob_start();

        if ($this->storeComparison->canSyncPrices() && $this->storeComparison->priceSyncRequiresTaxSync()) { ?>
        <p>
            <?php echo esc_html(
                sprintf(
                    __(
                        'If you sync prices, your tax settings in WooCommerce and PayPal Zettle need to match.
                        If the sync is disabled, you can edit taxes in your PayPal Zettle library.',
                        'zettle-pos-integration'
                    )
                )
            ); ?>
        </p>

        <p>
            <?php esc_html_e('Please choose an option below.', 'zettle-pos-integration'); ?>
        </p>
            <?php
        }

        $this->renderFormChoiceSelection();

        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function renderFormChoiceSelection(): string
    {
        $disabled = !$this->storeComparison->canSyncPrices();
        $syncByDefault = !$disabled && $this->storeComparison->includeTaxes();

        ob_start(); ?>

        <div class="form-choice-selection">
            <div class="form-choice-selector<?= $syncByDefault ? ' active' : '' ?>
                <?php echo $disabled ? ' disabled' : ''; ?>">
                <div class="form-choice-selector-input">
                    <input id="zettle-include-tax-prices" type="radio" name="woocommerce_zettle_sync_price_strategy"
                           value="<?= esc_attr(PriceSyncMode::ENABLED) ?>"
                           <?= $disabled ? 'disabled' : ($syncByDefault ? 'checked' : '') ?>
                    >
                </div>

                <div class="form-choice-selector-content">
                    <label for="zettle-include-tax-prices">
                        <?php esc_html_e('Sync prices', 'zettle-pos-integration'); ?>
                    </label>

                    <p class="form-choice-selector-content-description">
                        <?php esc_html_e(
                            'Edit prices at WooCommerce to keep them up-to-date in PayPal Zettle.',
                            'zettle-pos-integration'
                        ); ?>
                    </p>
                </div>
            </div>

            <div class="form-choice-selector<?= !$syncByDefault ? ' active' : '' ?>">
                <div class="form-choice-selector-input">
                    <input id="zettle-zero-prices" type="radio" name="woocommerce_zettle_sync_price_strategy"
                           value="<?= esc_attr(PriceSyncMode::DISABLED) ?>"
                           <?= !$syncByDefault ? 'checked' : ''; ?>>
                </div>

                <div class="form-choice-selector-content">
                    <label for="zettle-zero-prices">
                        <?php esc_html_e("Don't sync prices", 'zettle-pos-integration'); ?>
                    </label>

                    <p class="form-choice-selector-content-description">
                        <?php esc_html_e(
                            'Synced products will have the price set to 0. Update prices in your PayPal Zettle product library.',
                            'zettle-pos-integration'
                        ); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php return ob_get_contents();
    }

    /**
     * @inheritDoc
     */
    public function renderProceedButton(): string
    {
        return $this->renderActionButton(
            ButtonAction::PROCEED,
            __('Start sync', 'zettle-pos-integration')
        );
    }

    /**
     * @inheritDoc
     */
    public function renderBackButton(): string
    {
        return $this->renderActionButton(ButtonAction::BACK);
    }
}
