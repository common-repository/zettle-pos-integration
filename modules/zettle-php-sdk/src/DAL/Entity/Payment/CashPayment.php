<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\Type\PaymentType;

final class CashPayment extends AbstractPaymentMethod
{
    /**
     * @var float
     */
    private $handedAmount;

    /**
     * CashPayment constructor.
     *
     * @param string $uuid
     * @param float $amount
     * @param float $handedAmount
     */
    public function __construct(
        string $uuid,
        float $amount,
        float $handedAmount
    ) {

        parent::__construct($uuid, $amount, PaymentType::cashPayment());
        $this->handedAmount = $handedAmount;
    }

    /**
     * @return float
     */
    public function handedAmount(): float
    {
        return $this->handedAmount;
    }

    /**
     * @return float
     */
    public function changeAmount(): float
    {
        return ($this->handedAmount() - $this->amount());
    }
}
