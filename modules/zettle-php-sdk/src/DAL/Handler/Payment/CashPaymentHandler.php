<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Handler\Payment;

use Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment\CashPaymentBuilder;
use Inpsyde\Zettle\PhpSdk\DAL\Builder\Payment\CashPaymentBuilderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Payment\AbstractPaymentMethod;

class CashPaymentHandler extends AbstractPaymentHandler
{
    /**
     * @var CashPaymentBuilder
     */
    private $cashPaymentBuilder;

    /**
     * CashPaymentHandler constructor.
     *
     * @param string $validPaymentType
     * @param CashPaymentBuilderInterface $cashPaymentBuilder
     */
    public function __construct(
        string $validPaymentType,
        CashPaymentBuilderInterface $cashPaymentBuilder
    ) {
        parent::__construct($validPaymentType);
        $this->cashPaymentBuilder = $cashPaymentBuilder;
    }

    /**
     * @inheritDoc
     */
    public function serialize(AbstractPaymentMethod $payment): array
    {
        return $this->cashPaymentBuilder->createDataArray($payment);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(array $data): AbstractPaymentMethod
    {
        return $this->cashPaymentBuilder->buildFromArray($data);
    }
}
