<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Entity\Product;

use DateTime;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Image\ImageCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Metadata\Metadata;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Presentation\Presentation;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantOption\VariantOptionDefinitions;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Vat\Vat;

interface ProductInterface
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
     * @return ImageCollection
     */
    public function images(): ImageCollection;

    /**
     * @return VariantCollection
     */
    public function variants(): VariantCollection;

    /**
     * @return Presentation|null
     */
    public function presentation(): ?Presentation;

    /**
     * @return string|null
     */
    public function externalReference(): ?string;

    /**
     * @return string
     */
    public function etag(): ?string;

    /**
     * @return DateTime
     */
    public function updatedAt(): ?DateTime;

    /**
     * @return string
     */
    public function updatedBy(): ?string;

    /**
     * @return DateTime|null
     */
    public function createdAt(): ?DateTime;

    /**
     * @return Vat|null
     */
    public function vat(): ?Vat;

    public function taxExempt(): ?bool;

    public function usesDefaultTax(): ?bool;

    /**
     * @return string|null
     */
    public function unitName(): ?string;

    /**
     * @return Metadata|null
     */
    public function metadata(): ?Metadata;

    /**
     * @return VariantOptionDefinitions|null
     */
    public function variantOptionDefinitions(): ?VariantOptionDefinitions;
}
