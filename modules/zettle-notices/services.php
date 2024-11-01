<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Notices;

use Inpsyde\Zettle\Notices\Notice\Admin\CompleteOnboardingNotice;
use Inpsyde\Zettle\Notices\Notice\Admin\GlobalConnectionFailedNotice;
use Inpsyde\Zettle\Notices\Notice\Admin\IntegrationConnectionFailedNotice;
use Inpsyde\Zettle\Notices\Notice\NoticeDelegator;
use Inpsyde\Zettle\Notices\Notice\NoticeInterface;
use Psr\Container\ContainerInterface as C;

return [
    'zettle.notices.notification.notice.info.complete-onboarding' => static function (
        C $container
    ): NoticeInterface {
        return new CompleteOnboardingNotice(
            $container->get('zettle.settings.is-integration-page'),
            $container->get('zettle.settings.url')
        );
    },
    'zettle.notices.notification.notice.error.global.auth-failed' => static function (
        C $container
    ): NoticeInterface {
        return new GlobalConnectionFailedNotice(
            $container->get('zettle.settings.is-integration-page'),
            $container->get('zettle.auth.is-failed'),
            $container->get('zettle.settings.url')
        );
    },
    'zettle.notices.notification.notice.error.integration.auth-failed' => static function (
        C $container
    ): NoticeInterface {
        return new IntegrationConnectionFailedNotice(
            $container->get('zettle.settings.is-integration-page'),
            $container->get('zettle.onboarding.api-auth-check'),
            $container->get('zettle.settings.is-settings-save-request'),
            $container->get('zettle.settings.account.link.api-key-creation-url')
        );
    },
    'zettle.notices.notification.notices' => static function (C $container): array {
        return [
            $container->get('zettle.notices.notification.notice.info.complete-onboarding'),
            $container->get('zettle.notices.notification.notice.error.global.auth-failed'),
            $container->get('zettle.notices.notification.notice.error.integration.auth-failed'),
        ];
    },
    'zettle.notices.notification.delegator' => static function (C $container): NoticeDelegator {
        return new NoticeDelegator(
            ...$container->get('zettle.notices.notification.notices')
        );
    },
];
