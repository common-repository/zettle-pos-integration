<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Builder\Finance;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Finance\AccountTransaction;

interface AccountTransactionBuilderInterface
{
    /**
     * @param array $data
     *
     * @return AccountTransaction
     */
    public function buildFromArray(array $data): AccountTransaction;

    /**
     * @param AccountTransaction $accountTransaction
     *
     * @return array
     */
    public function createDataArray(AccountTransaction $accountTransaction): array;
}
