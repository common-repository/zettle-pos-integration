<?php

declare(strict_types=1);

// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
use Inpsyde\WcProductContracts\ProductType;
use Inpsyde\Zettle\PhpSdk\Builder\AttributeSetBuilder;
use Inpsyde\Zettle\PhpSdk\Builder\BuilderInterface as B;
use Inpsyde\Zettle\PhpSdk\Builder\CallbackBuilder;
use Inpsyde\Zettle\PhpSdk\Builder\PriceBuilder;
use Inpsyde\Zettle\PhpSdk\Builder\VariantBuilder;
use Inpsyde\Zettle\PhpSdk\Builder\VariantOptionCollectionBuilder;
use Inpsyde\Zettle\PhpSdk\Builder\VariantOptionDefinitionsBuilder;
use Inpsyde\Zettle\PhpSdk\DAL\Builder\Repository\Variant\VariantBuilderRepositoryInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Image\ConcreteImage;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Image\ImageCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Image\ImageInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Image\LazyImage;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Metadata\Metadata;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Metadata\Source;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Organization\TaxationType;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Presentation\Presentation;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Price\Price;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Product\Product;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Product\ProductInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Variant\VariantInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Variant\WcVariationIterator;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantOption\AttributeSet;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\VariantOption\VariantOptionDefinitions;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Vat\Vat;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Image\UrlProviderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Vat\VatProvider;
use Inpsyde\Zettle\PhpSdk\Exception\BuilderException;
use Inpsyde\Zettle\PhpSdk\Exception\IdNotFoundException;
use Inpsyde\Zettle\PhpSdk\Iterator\WcProductAttachmentIterator;
use Inpsyde\Zettle\PhpSdk\Map\OneToOneMapInterface;
use Inpsyde\Zettle\PhpSdk\Uuid\Uuid;
use Psr\Container\ContainerInterface as C;

$key = static function (string $className): string {
    return "zettle.sdk.builder.woocommerce.{$className}";
};

$builder = static function (callable $callable) {
    return static function (C $container) use ($callable): B {
        return new CallbackBuilder(
            static function (string $className, $payload, B $builder) use ($callable, $container) {
                return $callable($payload, $builder, $container);
            }
        );
    };
};

return [
    $key(ProductInterface::class) => $builder(
        static function (WC_Product $wcProduct, B $builder, C $container) {
            $presentation = null;
            $imageId = (int) $wcProduct->get_image_id();

            if ($imageId) {
                try {
                    $presentation = $builder->build(Presentation::class, $wcProduct);
                } catch (BuilderException $exception) {
                    $container->get('inpsyde.debug.exception-handler')->handle($exception);
                }
            }

            $taxationType = $container->get('zettle.sync.taxation-type');
            $priceSyncEnabled = $container->get('zettle.sync.price-sync-enabled');

            $taxStatus = $wcProduct->get_tax_status();
            // IZET-374, can send taxExempt=true only for sales tax, for others should be false
            $taxExempt = $taxStatus !== 'taxable' && $taxationType === TaxationType::SALES_TAX;

            $useDefaultTax = $taxationType === TaxationType::SALES_TAX
                || ($taxationType === TaxationType::VAT && !$priceSyncEnabled);

            $vat = ($taxationType === TaxationType::VAT && !$useDefaultTax)
                ? $builder->build(Vat::class, $wcProduct)
                : null;

            $product = new Product(
                (string) Uuid::fromWcProduct($wcProduct),
                $wcProduct->get_name(),
                $wcProduct->get_description(),
                $builder->build(ImageCollection::class, $wcProduct),
                $builder->build(VariantCollection::class, $wcProduct),
                $presentation,
                null,
                null,
                null,
                null,
                new DateTime('now'),
                $vat,
                $taxExempt,
                $useDefaultTax,
                null,
                new Metadata(true, $builder->build(Source::class, $wcProduct))
            );

            if (
                $wcProduct instanceof WC_Product_Variable
                && !empty($wcProduct->get_visible_children())
            ) {
                $product->setVariantOptionDefinitions(
                    $builder->build(VariantOptionDefinitions::class, $wcProduct)
                );
            }

            return $product;
        }
    ),
    $key(VariantInterface::class) => static function (C $container) {
        return new VariantBuilder(
            static function (Throwable $exception) use ($container): void {
                $container->get('inpsyde.debug.exception-handler')->handle($exception);
            },
            $container->get('zettle.sync.taxation-type'),
            $container->get('zettle.sync.price-sync-enabled'),
            $container->get('zettle.product-settings.barcode.repository')
        );
    },
    $key(VariantCollection::class) => $builder(
        static function (WC_Product $wcProduct, B $builder, C $container) {
            $collection = new VariantCollection();

            $variantBuilderRepository = $container->get('zettle.sdk.builder.repository.variant');
            assert($variantBuilderRepository instanceof VariantBuilderRepositoryInterface);

            switch (true) {
                case $wcProduct->is_type(ProductType::VARIABLE):
                    assert($wcProduct instanceof WC_Product_Variable);

                    // Case: Variable Products without Variations
                    if (empty($wcProduct->get_visible_children())) {
                        return $collection;
                    }

                    $variationIterator = new WcVariationIterator(
                        $wcProduct,
                        $builder,
                        $container->get('zettle.sdk.repository.woocommerce.product')
                    );

                    foreach ($variationIterator as $variationId => $variant) {
                        if (!($variant instanceof VariantInterface)) {
                            continue;
                        }

                        $collection->add($variant);
                    }

                    return $collection;
                case $wcProduct->is_type(ProductType::SIMPLE):
                    return $variantBuilderRepository->addToCollection($wcProduct, $collection);
            }

            return $collection;
        }
    ),
    $key(VariantOptionCollection::class) => static function (C $container): B {
        return new VariantOptionCollectionBuilder();
    },
    $key(VariantOptionDefinitions::class) => static function (C $container): B {
        return new VariantOptionDefinitionsBuilder();
    },
    $key(AttributeSet::class) => static function (C $container): B {
        return new AttributeSetBuilder(
            $container->get('zettle.sdk.repository.woocommerce.product')
        );
    },
    $key(ImageInterface::class) => $builder(
        static function (WC_Product $wcProduct, B $builder, C $container) {
            $imageIdMap = $container->get('zettle.sdk.id-map.image');
            assert($imageIdMap instanceof OneToOneMapInterface);

            $imageId = (int) $wcProduct->get_image_id();

            try {
                return new ConcreteImage($imageIdMap->remoteId($imageId));
            } catch (IdNotFoundException $exception) {
                $urlProvider = $container->get('zettle.sdk.dal.provider.image.url');
                assert($urlProvider instanceof UrlProviderInterface);

                return new LazyImage(
                    $imageId,
                    $urlProvider,
                    $container->get('zettle.sdk.api.images'),
                    $imageIdMap
                );
            }
        }
    ),
    $key(ImageCollection::class) => $builder(
        static function (WC_Product $wcProduct, B $builder, C $container) {
            $imageIdMap = $container->get('zettle.sdk.id-map.image');
            $urlProvider = $container->get('zettle.sdk.dal.provider.image.url');
            $imageClient = $container->get('zettle.sdk.api.images');
            assert($imageIdMap instanceof OneToOneMapInterface);
            assert($urlProvider instanceof UrlProviderInterface);
            $imageIds = new WcProductAttachmentIterator($wcProduct, 10);
            $images = [];
            foreach ($imageIds as $imageId) {
                try {
                    $images[] = new ConcreteImage($imageIdMap->remoteId($imageId));
                } catch (IdNotFoundException $exception) {
                    $images[] = new LazyImage(
                        $imageId,
                        $urlProvider,
                        $imageClient,
                        $imageIdMap
                    );
                }
            }

            return new ImageCollection(...$images);
        }
    ),
    $key(Presentation::class) => $builder(
        static function (WC_Product $wcProduct, B $builder, C $container) {
            return new Presentation(
                $builder->build(ImageInterface::class, $wcProduct)
            );
        }
    ),
    $key(Price::class) => static function (C $container): B {
        return new PriceBuilder(
            $container->get('zettle.sdk.config.woocommerce-config'),
            $container->get('zettle.sync.taxation-mode')
        );
    },
    $key(Vat::class) => $builder(
        static function (WC_Product $wcProduct, B $builder, C $container) {
            $vatProvider = $container->get('zettle.sdk.dal.provider.vat.wc');
            assert($vatProvider instanceof VatProvider);

            return $vatProvider->provide($wcProduct);
        }
    ),
    $key(Source::class) => $builder(
        static function (WC_Product $product, B $builder, C $container) {
            return new Source('WooCommerce', true);
        }
    ),
];
