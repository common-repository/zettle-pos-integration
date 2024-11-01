<?php

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Variant;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Presentation\Presentation;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Price\Price;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Vat\Vat;

interface VariantInterface
{

    /**
     * @return string
     */
    public function uuid(): string;

    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return string
     */
    public function description(): string;

    /**
     * @return string
     */
    public function sku(): string;

    /**
     * @return Price|null
     */
    public function price(): ?Price;

    /**
     * @return Vat|null
     */
    public function vat(): ?Vat;

    /**
     * @return Presentation
     */
    public function presentation(): ?Presentation;

    /**
     * @return string|null
     */
    public function unitName(): ?string;

    /**
     * @return VariantOptionCollection|null
     */
    public function options(): ?VariantOptionCollection;

    /**
     * @return Price|null
     */
    public function costPrice(): ?Price;

    /**
     * @return string|null
     */
    public function barcode(): ?string;
}
