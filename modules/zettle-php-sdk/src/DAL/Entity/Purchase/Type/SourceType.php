<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Purchase\Type;

use Werkspot\Enum\AbstractEnum;

/**
 * @method static SourceType posSource()
 * @method bool isPosSource()
 * @method static SourceType shopSource()
 * @method bool isShopSource()
 * @method static SourceType SDKSource()
 * @method bool isSDKSource()
 */
class SourceType extends AbstractEnum
{
    public const POS = 'POS';
    public const SHOP = 'WEB_SHOP';
    public const SDK = 'SDK';
}
