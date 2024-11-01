<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk;

use Dhii\Collection\MutableContainerInterface;
use Http\Message\UriFactory;
use Inpsyde\Zettle\PhpSdk\API\Image\Images;
use Inpsyde\Zettle\PhpSdk\API\Inventory\Inventory;
use Inpsyde\Zettle\PhpSdk\API\Inventory\Locations;
use Inpsyde\Zettle\PhpSdk\API\Listener\Products\OnSuccessDeleteProductsListener;
use Inpsyde\Zettle\PhpSdk\API\OAuth\Organizations;
use Inpsyde\Zettle\PhpSdk\API\OAuth\Users;
use Inpsyde\Zettle\PhpSdk\API\Products\Products;
use Inpsyde\Zettle\PhpSdk\API\Taxes\Taxes;
use Inpsyde\Zettle\PhpSdk\API\Webhooks\Entity\PayloadFactory;
use Inpsyde\Zettle\PhpSdk\API\Webhooks\Entity\WebhookFactory;
use Inpsyde\Zettle\PhpSdk\API\Webhooks\Entity\ZettlePayloadFactory;
use Inpsyde\Zettle\PhpSdk\API\Webhooks\Entity\ZettleWebhookFactory;
use Inpsyde\Zettle\PhpSdk\API\Webhooks\Subscriptions;
use Inpsyde\Zettle\PhpSdk\Builder\ArrayBuilder;
use Inpsyde\Zettle\PhpSdk\Builder\BuilderInterface;
use Inpsyde\Zettle\PhpSdk\Builder\ContainerAwareBuilder;
use Inpsyde\Zettle\PhpSdk\Builder\FilterableBuilder;
use Inpsyde\Zettle\PhpSdk\Builder\TypeDelegatingBuilder;
use Inpsyde\Zettle\PhpSdk\Builder\ValidatableBuilder;
use Inpsyde\Zettle\PhpSdk\Builder\WooCommerceBuilder;
use Inpsyde\Zettle\PhpSdk\Config\WooCommerceConfigContainer;
use Inpsyde\Zettle\PhpSdk\DAL\Builder\Repository\Variant\VariantBuilderRepository;
use Inpsyde\Zettle\PhpSdk\DAL\Builder\Repository\Variant\VariantBuilderRepositoryInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Connection\ConnectionType;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Image\PlaceholderUrlProvider;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Image\UrlProviderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Image\WordPressFilePathProvider;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Image\WordpressUrlProvider;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Organization\OrganizationProvider;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Organization\RestOrganizationProvider;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Organization\TransientCachingOrganizationProvider;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Vat\VatProvider;
use Inpsyde\Zettle\PhpSdk\DAL\Provider\Vat\WooCommerceVatProvider;
use Inpsyde\Zettle\PhpSdk\DAL\Validator\Vat\VatValidator;
use Inpsyde\Zettle\PhpSdk\DB\DataMappingTable;
use Inpsyde\Zettle\PhpSdk\DB\Table;
use Inpsyde\Zettle\PhpSdk\Exception\ZettleRestException;
use Inpsyde\Zettle\PhpSdk\Factory\WcProductFactory;
use Inpsyde\Zettle\PhpSdk\Factory\WcProductFactoryInterface;
use Inpsyde\Zettle\PhpSdk\Filter\CompoundFilter;
use Inpsyde\Zettle\PhpSdk\Filter\FilterInterface;
use Inpsyde\Zettle\PhpSdk\Filter\ImageConnectionFilter;
use Inpsyde\Zettle\PhpSdk\Filter\ProductConnectionFilter;
use Inpsyde\Zettle\PhpSdk\Filter\StockQuantityFilter;
use Inpsyde\Zettle\PhpSdk\Filter\TaxFilter;
use Inpsyde\Zettle\PhpSdk\Filter\VariantConnectionFilter;
use Inpsyde\Zettle\PhpSdk\Image\ExifImageFormatRetriever;
use Inpsyde\Zettle\PhpSdk\Image\ExtensionImageFormatRetriever;
use Inpsyde\Zettle\PhpSdk\Image\ImageFormatRetrieverInterface;
use Inpsyde\Zettle\PhpSdk\Map\WpdbMap;
use Inpsyde\Zettle\PhpSdk\Provider\BootstrapProvider;
use Inpsyde\Zettle\PhpSdk\Repository\WooCommerce\Product\ProductRepository as WcProductRepository;
use Inpsyde\Zettle\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface as WcProductRepositoryInterface;
use Inpsyde\Zettle\PhpSdk\Repository\Zettle\Product\ProductRepository;
use Inpsyde\Zettle\PhpSdk\Repository\Zettle\Product\ProductRepositoryInterface;
use Inpsyde\Zettle\PhpSdk\Serializer\ContainerAwareEntitySerializer;
use Inpsyde\Zettle\PhpSdk\Serializer\SerializerInterface;
use Inpsyde\Zettle\PhpSdk\Validator\CompoundValidator;
use Inpsyde\Zettle\PhpSdk\Validator\LocalImageValidator;
use Inpsyde\Zettle\PhpSdk\Validator\PresentationValidator;
use Inpsyde\Zettle\PhpSdk\Validator\ProductValidator;
use Inpsyde\Zettle\PhpSdk\Validator\ProductVariantOptionDefinitionsValidator;
use Inpsyde\Zettle\PhpSdk\Validator\StockValidator;
use Inpsyde\Zettle\PhpSdk\Validator\ValidatorInterface;
use Inpsyde\Zettle\PhpSdk\Validator\VariableProductVatValidator;
use Inpsyde\Zettle\PhpSdk\Validator\VariantOptionDefinitionsValidator;
use Inpsyde\Zettle\PhpSdk\Validator\VariantOptionValidator;
use Inpsyde\Zettle\PhpSdk\Validator\WordPressImageValidator;
use Inpsyde\Zettle\Provider;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerInterface as C;
use Symfony\Component\Uid\Uuid;
use wpdb;

return array_merge(
    [
        'zettle.sdk.dal.table.name' => static function (): string {
            return 'zettle_woocommerce_id_map';
        },
        'zettle.sdk.dal.table' => static function (C $container): Table {
            return new DataMappingTable($container->get('zettle.sdk.dal.table.name'));
        },
        'zettle.sdk.option.integration' => static function (C $container): string {
            return 'sdk.integration-id';
        },
        /**
         * This UUID is used when syncing inventory.
         * Incoming webhooks will pass the UUID through back to us so we can determine
         * whether or not the change was triggered from our end.
         *
         * TODO Maybe simply ALWAYS return a new UUID here and handle caching via extension
         */
        'zettle.sdk.integration-id' => static function (C $container): string {
            if (!$container->has('zettle.sdk.integration-id.container')) {
                return (string) Uuid::v1();
            }
            $idContainer = $container->get('zettle.sdk.integration-id.container');
            assert($idContainer instanceof MutableContainerInterface);
            $key = $container->get('zettle.sdk.option.integration');

            if (!$idContainer->has($key)) {
                $idContainer->set(
                    $key,
                    (string) Uuid::v1()
                );
            }

            return $idContainer->get($key);
        },
        'zettle.sdk.serializer' => static function (C $container): SerializerInterface {
            return new ContainerAwareEntitySerializer(
                new NamespacedContainer(
                    'zettle.sdk.serializer',
                    $container
                )
            );
        },
        'zettle.sdk.builder' => static function (C $container): BuilderInterface {
            $arrayBuilderContainer = new NamespacedContainer(
                'zettle.sdk.builder.array',
                $container
            );
            $wooBuilderContainer = new NamespacedContainer(
                'zettle.sdk.builder.woocommerce',
                $container
            );
            $typedDelegatingBuilder = new TypeDelegatingBuilder(
                new ArrayBuilder(new ContainerAwareBuilder($arrayBuilderContainer)),
                new WooCommerceBuilder(new ContainerAwareBuilder($wooBuilderContainer))
            );
            $filteredBuilder = new FilterableBuilder(
                $typedDelegatingBuilder,
                $container->get('zettle.sdk.filter')
            );

            return new ValidatableBuilder(
                $filteredBuilder,
                $container->get('zettle.sdk.validator')
            );
        },
        'zettle.sdk.builder.repository.variant' =>
            static function (C $container): VariantBuilderRepositoryInterface {
                return new VariantBuilderRepository(
                    $container->get('zettle.sdk.builder')
                );
            },
        'zettle.sdk.filters.product-connection' =>
            static function (C $container): FilterInterface {
                return new ProductConnectionFilter(
                    $container->get('zettle.sdk.id-map.product'),
                    static function () use ($container): Products {
                        return $container->get('zettle.sdk.api.products');
                    }
                );
            },
        'zettle.sdk.filters.variant-connection' =>
            static function (C $container): FilterInterface {
                return new VariantConnectionFilter(
                    $container->get('zettle.sdk.id-map.variant')
                );
            },
        'zettle.sdk.filters.image-connection' => static function (C $container): FilterInterface {
            return new ImageConnectionFilter();
        },
        'zettle.sdk.filters.tax' => static function (C $container): FilterInterface {
            return new TaxFilter(
                static function () use ($container): string {
                    return $container->get('zettle.sync.taxation-type');
                }
            );
        },
        'zettle.sdk.filters' => static function (C $container): array {
            return [
                $container->get('zettle.sdk.filters.product-connection'),
                $container->get('zettle.sdk.filters.variant-connection'),
                $container->get('zettle.sdk.filters.image-connection'),
                $container->get('zettle.sdk.filters.tax'),
            ];
        },
        'zettle.sdk.filter' => static function (C $container): FilterInterface {
            return new CompoundFilter(
                ...$container->get('zettle.sdk.filters')
            );
        },
        'zettle.sdk.validator.product' => static function (C $container): ValidatorInterface {
            return new ProductValidator();
        },
        'zettle.sdk.validator.variable-product-vat' => static function (C $container): ValidatorInterface {
            return new VariableProductVatValidator();
        },
        'zettle.sdk.validator.presentation' => static function (C $container): ValidatorInterface {
            return new PresentationValidator();
        },
        'zettle.sdk.validator.variant-option-definitions' =>
            static function (C $container): ValidatorInterface {
                return new VariantOptionDefinitionsValidator();
            },
        'zettle.sdk.validator.product-with-variants' =>
            static function (C $container): ValidatorInterface {
                return new ProductVariantOptionDefinitionsValidator();
            },

        'zettle.sdk.validator.local-image' => static function (C $container): ValidatorInterface {
            return new LocalImageValidator(
                $container->get('zettle.sdk.dal.provider.image.file'),
                $container->get('zettle.sdk.validator.image.exif-supported-types'),
                $container->get('zettle.sdk.validator.image.min-file-size'),
                $container->get('zettle.sdk.validator.image.max-file-size'),
                $container->get('zettle.sdk.validator.image.min-width'),
                $container->get('zettle.sdk.validator.image.min-height'),
                $container->get('zettle.sdk.validator.image.max-width'),
                $container->get('zettle.sdk.validator.image.max-height')
            );
        },
        'zettle.sdk.validator.wp-image' => static function (C $container): ValidatorInterface {
            return new WordPressImageValidator(
                $container->get('zettle.sdk.validator.image.supported-types'),
                $container->get('zettle.sdk.validator.image.min-file-size'),
                $container->get('zettle.sdk.validator.image.max-file-size'),
                $container->get('zettle.sdk.validator.image.min-width'),
                $container->get('zettle.sdk.validator.image.min-height'),
                $container->get('zettle.sdk.validator.image.max-width'),
                $container->get('zettle.sdk.validator.image.max-height')
            );
        },
        'zettle.sdk.validator.image' => static function (C $container): ValidatorInterface {
            return $container->get('zettle.sdk.validator.wp-image');
        },
        'zettle.sdk.validator.image.supported-types' => static function (C $container): array {
            return [
                'gif',
                'jpeg',
                'png',
                'bmp',
                'tiff',
            ];
        },
        'zettle.sdk.validator.image.exif-supported-types' => static function (C $container): array {
            return [
                IMAGETYPE_GIF => 'GIF',
                IMAGETYPE_JPEG => 'JPEG',
                IMAGETYPE_PNG => 'PNG',
                IMAGETYPE_BMP => 'BMP',
                IMAGETYPE_TIFF_II => 'TIFF',
                IMAGETYPE_TIFF_MM => 'TIFF',
            ];
        },
        'zettle.sdk.validator.image.min-file-size' => static function (C $container): int {
            return 2500;
        },
        'zettle.sdk.validator.image.max-file-size' => static function (C $container): int {
            return 5242880;
        },
        'zettle.sdk.validator.image.min-width' => static function (C $container): int {
            return 50;
        },
        'zettle.sdk.validator.image.min-height' => static function (C $container): int {
            return 50;
        },
        'zettle.sdk.validator.image.max-width' => static function (C $container): int {
            return 5000;
        },
        'zettle.sdk.validator.image.max-height' => static function (C $container): int {
            return 5000;
        },

        'zettle.sdk.validator.stock' => static function (C $container): ValidatorInterface {
            return new StockValidator(
                $container->get('zettle.sdk.validator.stock.max')
            );
        },
        'zettle.sdk.validator.stock.max' => static function (C $container): int {
            return 99999;
        },

        'zettle.sdk.validator.variant-option' => static function (
            C $container
        ): ValidatorInterface {
            return new VariantOptionValidator();
        },

        'zettle.sdk.validators' => static function (C $container): array {
            return [
                $container->get('zettle.sdk.validator.product'),
                $container->get('zettle.sdk.validator.variable-product-vat'),
                $container->get('zettle.sdk.validator.presentation'),
                $container->get('zettle.sdk.validator.variant-option-definitions'),
                $container->get('zettle.sdk.validator.product-with-variants'),
                $container->get('zettle.sdk.validator.image'),
                $container->get('zettle.sdk.validator.variant-option'),
                $container->get('zettle.sdk.validator.stock'),
            ];
        },
        'zettle.sdk.validator' => static function (C $container): ValidatorInterface {
            return new CompoundValidator(
                ...$container->get('zettle.sdk.validators')
            );
        },

        'zettle.sdk.wpdb' => static function (): wpdb {
            global $wpdb;

            return $wpdb;
        },
        'zettle.sdk.id-map.product' => static function (C $container): WpdbMap {
            return new WpdbMap(
                $container->get('zettle.sdk.wpdb'),
                $container->get('zettle.sdk.dal.table'),
                ConnectionType::PRODUCT,
                $container->get('zettle.current-site-id')
            );
        },
        'zettle.sdk.id-map.variant' => static function (C $container): WpdbMap {
            return new WpdbMap(
                $container->get('zettle.sdk.wpdb'),
                $container->get('zettle.sdk.dal.table'),
                ConnectionType::VARIANT,
                $container->get('zettle.current-site-id')
            );
        },
        'zettle.sdk.id-map.image' => static function (C $container): WpdbMap {
            return new WpdbMap(
                $container->get('zettle.sdk.wpdb'),
                $container->get('zettle.sdk.dal.table'),
                ConnectionType::IMAGE,
                $container->get('zettle.current-site-id')
            );
        },
        'zettle.sdk.bootstrap' => static function (C $container): Bootstrap {
            return new Bootstrap($container->get('zettle.sdk.dal.table'));
        },
        'zettle.sdk.provider.bootstrap' => static function (C $container): Provider {
            return new BootstrapProvider(
                $container->get('zettle.sdk.bootstrap')
            );
        },
        'zettle.sdk.provider' => static function (C $container): array {
            return [
                $container->get('zettle.sdk.provider.bootstrap'),
            ];
        },
        'zettle.sdk.config.woocommerce-config' =>
            static function (C $container): ContainerInterface {
                return new WooCommerceConfigContainer();
            },
        'zettle.sdk.placeholder-image-url' => static function (C $container): string {
            $envUrl = getenv('IZETTLE_PLACEHOLDER_IMAGE_URL');
            if ($envUrl) {
                return (string) $envUrl;
            }

            return 'https://placehold.co/200x200.jpg?text=WooProduct';
        },
        'zettle.sdk.dal.provider.image.url' =>
            static function (C $container): UrlProviderInterface {
                if (getenv('IZETTLE_PLACEHOLDER_IMAGES_ENABLED') === '1') {
                    return new PlaceholderUrlProvider($container->get('zettle.sdk.placeholder-image-url'));
                }

                return new WordpressUrlProvider();
            },
        'zettle.sdk.dal.provider.image.file' =>
            static function (C $container): UrlProviderInterface {
                return new WordPressFilePathProvider();
            },
        'zettle.sdk.dal.provider.organization.transient-key' => static function (): string {
            return 'zettle_organization';
        },
        'zettle.sdk.dal.provider.organization' =>
            static function (C $container): OrganizationProvider {
                return new TransientCachingOrganizationProvider(
                    new RestOrganizationProvider(
                        $container->get('zettle.sdk.api.oauth.organizations')
                    ),
                    $container->get('zettle.sdk.dal.provider.organization.transient-key'),
                    $container->get('zettle.sdk.dal.provider.organization.transient-expiration')
                );
            },
        'zettle.sdk.dal.provider.organization.transient-expiration' =>
            static function (): int {
                return 5 * 60; // 5 minutes
            },
        'zettle.sdk.dal.provider.vat.wc' => static function (C $container): VatProvider {
            return new WooCommerceVatProvider(
                $container->get('zettle.wc.shop.location')
            );
        },
        'zettle.sdk.rest-client' => static function (C $container): RestClientInterface {
            return new Psr18RestClient(
                $container->get('zettle.logger.woocommerce'),
                $container->get('inpsyde.http-client'),
                $container->get('inpsyde.http-client.uri-factory'),
                $container->get('inpsyde.http-client.request-factory'),
                $container->get('inpsyde.http-client.stream-factory')
            );
        },
        'zettle.sdk.api.oauth.users' => static function (C $container): Users {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Users(
                $container->get('zettle.logger.woocommerce'),
                $uriFactory->createUri('https://oauth.izettle.com'),
                $container->get('zettle.sdk.rest-client')
            );
        },
        'zettle.sdk.api.oauth.organizations' => static function (C $container): Organizations {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Organizations(
                $uriFactory->createUri('https://secure.izettle.com'),
                $container->get('zettle.sdk.rest-client'),
                $container->get('zettle.sdk.builder')
            );
        },
        'zettle.sdk.api.listener.delete.product' =>
            static function (C $container): OnSuccessDeleteProductsListener {
                return new OnSuccessDeleteProductsListener(
                    $container->get('zettle.sdk.repository.zettle.product'),
                    $container->get('inpsyde.queue.repository'),
                    $container->get('inpsyde.queue.create-job-record'),
                    $container->get('inpsyde.queue.logger')
                );
            },
        'zettle.sdk.api.products.listener.update' => static function (C $container): callable {
            //phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
            return static function (string $operation, $payload, bool $success) use ($container) {
                //Silence. This is only here so that extensions can add actual listeners
            };
            //phpcs:enable
        },
        'zettle.sdk.api.products.listener.delete' => static function (C $container): callable {
            //phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType

            return static function (string $operation, $payload, bool $success) use ($container) {
                $productsDeleteListener = $container->get('zettle.sdk.api.listener.delete.product');

                if (!$productsDeleteListener->accepts($operation, $payload, $success)) {
                    return;
                }

                $productsDeleteListener->execute($payload);
            };
            //phpcs:enable
        },
        'zettle.sdk.api.products' => static function (C $container): Products {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Products(
                $uriFactory->createUri('https://products.izettle.com'),
                $container->get('zettle.sdk.rest-client'),
                $container->get('zettle.sdk.builder'),
                $container->get('zettle.sdk.serializer'),
                $container->get('zettle.sdk.api.products.listener.delete'),
                $container->get('zettle.sdk.api.products.listener.update')
            );
        },
        'zettle.sdk.api.images' => static function (C $container): Images {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Images(
                $uriFactory->createUri('https://image.izettle.com'),
                $container->get('zettle.sdk.rest-client'),
                $container->get('zettle.sdk.builder'),
                $container->get('zettle.sdk.image.format-retriever'),
                $container->get('zettle.logger')
            );
        },
        'zettle.sdk.api.webhooks.factory' => static function (): WebhookFactory {
            return new ZettleWebhookFactory();
        },
        'zettle.sdk.api.webhooks' => static function (C $container): Subscriptions {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Subscriptions(
                $uriFactory->createUri('https://pusher.izettle.com'),
                $container->get('zettle.sdk.rest-client'),
                $container->get('zettle.sdk.api.webhooks.factory')
            );
        },
        'zettle.sdk.api.webhooks.payload.factory' => static function (): PayloadFactory {
            return new ZettlePayloadFactory();
        },
        'zettle.sdk.api.inventory' => static function (C $container): Inventory {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Inventory(
                $uriFactory->createUri('https://inventory.izettle.com'),
                $container->get('zettle.sdk.rest-client'),
                $container->get('zettle.sdk.api.inventory.locations'),
                $container->get('zettle.sdk.builder'),
                $container->get('zettle.sdk.integration-id')
            );
        },
        'zettle.sdk.api.taxes' => static function (C $container): Taxes {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Taxes(
                $uriFactory->createUri('https://products.izettle.com'),
                $container->get('zettle.sdk.rest-client'),
                $container->get('zettle.sdk.builder')
            );
        },
        'zettle.sdk.api.inventory.locations' => static function (C $container): Locations {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Locations(
                $uriFactory->createUri('https://inventory.izettle.com'),
                $container->get('zettle.sdk.rest-client'),
                $container->get('zettle.sdk.builder')
            );
        },
        'zettle.sdk.api.auth-check' => static function (C $container): callable {
            return static function () use ($container): bool {
                $users = $container->get('zettle.sdk.api.oauth.users');
                assert($users instanceof Users);

                try {
                    $users->me();

                    return true;
                } catch (ZettleRestException $exception) {
                    // Logging needed?
                }

                return false;
            };
        },
        'zettle.sdk.repository.woocommerce.product' => static function (
            C $container
        ): WcProductRepositoryInterface {
            return new WcProductRepository();
        },
        'zettle.sdk.repository.zettle.product' => static function (
            C $container
        ): ProductRepositoryInterface {
            return new ProductRepository(
                $container->get('zettle.sdk.id-map.product')
            );
        },
        'zettle.sdk.factory.woocommerce.product' => static function (
            C $container
        ): WcProductFactoryInterface {
            return new WcProductFactory(
                $container->get('zettle.sdk.repository.zettle.product'),
                $container->get('zettle.sdk.repository.woocommerce.product')
            );
        },

        'zettle.sdk.image.format-retrievers.exif' => static function (
            C $container
        ): ImageFormatRetrieverInterface {
            return new ExifImageFormatRetriever();
        },
        'zettle.sdk.image.format-retrievers.extension' => static function (
            C $container
        ): ImageFormatRetrieverInterface {
            return new ExtensionImageFormatRetriever();
        },
        'zettle.sdk.image.format-retriever' => static function (
            C $container
        ): ImageFormatRetrieverInterface {
            return $container->get('zettle.sdk.image.format-retrievers.extension');
        },
    ],
    require __DIR__ . '/builders.array.php',
    require __DIR__ . '/builders.woocommerce.php',
    require __DIR__ . '/serializers.php'
);
