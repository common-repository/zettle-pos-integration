<?php

namespace Inpsyde\Zettle\PhpSdk\DAL\Provider\Vat;

use Exception;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Vat\Vat;
use Inpsyde\Zettle\PhpSdk\DAL\Exception\Vat\VatNotFound;
use WC_Product;

interface VatProvider
{

    /**
     * @param WC_Product $wcProduct
     * @return Vat
     * @throws VatNotFound
     * @throws Exception
     */
    public function provide(WC_Product $wcProduct): Vat;
}
