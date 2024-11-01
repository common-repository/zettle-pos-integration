<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Finance\Type;

use Werkspot\Enum\AbstractEnum;

/**
 * @method static PeriodType daily()
 * @method bool isDaily()
 * @method static PeriodType weekly()
 * @method bool isWeekly()
 * @method static PeriodType monthly()
 * @method bool isMonthly()
 */
final class PeriodType extends AbstractEnum
{
    public const DAILY = 'DAILY';
    public const WEEKLY = 'WEEKLY';
    public const MONTHLY = 'MONTHLY';
}
