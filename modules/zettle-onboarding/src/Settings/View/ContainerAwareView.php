<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Onboarding\Settings\View;

use Inpsyde\StateMachine\StateMachineInterface;
use Inpsyde\Zettle\Onboarding\OnboardingState;
use Psr\Container\ContainerInterface;

class ContainerAwareView implements OnboardingView
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var OnboardingView
     */
    private $view;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function renderHeader(): string
    {
        return $this->view()->renderHeader();
    }

    public function renderContent(): string
    {
        return $this->view()->renderContent();
    }

    public function renderProceedButton(): string
    {
        return $this->view()->renderProceedButton();
    }

    public function renderBackButton(): string
    {
        return $this->view()->renderBackButton();
    }

    /**
     * @return OnboardingView
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
     * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
     */
    private function view(): OnboardingView
    {
        if ($this->view instanceof OnboardingView) {
            return $this->view;
        }

        $stateMachine = $this->container->get('inpsyde.state-machine');
        assert($stateMachine instanceof StateMachineInterface);

        switch ($stateMachine->currentState()->name()) {
            case OnboardingState::WELCOME:
                $this->view = new WelcomeView(
                    $this->container->get('zettle.onboarding.zettle-link')
                );
                break;
            case OnboardingState::API_CREDENTIALS:
                $this->view = new ApiCredentialsView(
                    $this->container->get('zettle.settings.wc-integration'),
                    $this->container->get('zettle.settings.account.link.api-key-creation'),
                    __('Authorise connection', 'zettle-pos-integration'),
                    __(
                        'Please paste the API key in the field below.',
                        'zettle-pos-integration'
                    ),
                    [
                        'api_key',
                    ]
                );
                break;
            case OnboardingState::INVALID_CREDENTIALS:
                $this->view = (new SimpleView(
                    __('Authentication failed', 'zettle-pos-integration'),
                    __(
                        "We could not authenticate with the credentials you provided.
                        Press 'Start over' to re-enter your credentials.
                        ",
                        'zettle-pos-integration'
                    )
                ))
                    ->withProceedButton(__('Start over', 'zettle-pos-integration'));
                break;
            case OnboardingState::SYNC_PARAM_VAT:
                $this->view = $this->container->get('zettle.onboarding.settings.view.sync-vat-param');
                break;
            case OnboardingState::SYNC_PARAM_PRODUCTS:
                $this->view = $this->container->get('zettle.onboarding.settings.view.product-sync-params');
                break;
            case OnboardingState::SYNC_PROGRESS:
                $this->view = $this->container->get('zettle.onboarding.settings.view.sync-progress');
                break;
            case OnboardingState::SYNC_FINISHED:
                $this->view = new SyncFinishedView(
                    $this->container->get('zettle.onboarding.zettle-products-link')
                );
                break;
            case OnboardingState::ONBOARDING_COMPLETED:
                $this->view = $this->container->get('zettle.onboarding.settings.view.onboarding-completed');
                break;
            case OnboardingState::UNHANDLED_ERROR:
                $this->view = new UnhandledErrorView();
                break;
            default:
                $this->view = (new SimpleView(
                    sprintf(
                        '%s__WHOOPS__',
                        $stateMachine->currentState()->name()
                    ),
                    ''
                ))
                    ->withBackButton();
        }

        return $this->view;
    }
}
