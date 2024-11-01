<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\ProductDebug;

use Inpsyde\Zettle\ProductDebug\Cli\ProductsCommand;
use Inpsyde\Zettle\ProductDebug\Listing\CustomColumn;
use Inpsyde\Zettle\ProductDebug\Rest\V1\EndpointInterface;
use Inpsyde\Zettle\ProductDebug\Rest\V1\ProductValidationEndpoint;
use Psr\Container\ContainerInterface as C;

return [
    'zettle.product.debug.listing.custom-column' =>
        static function (C $container): CustomColumn {
            return new CustomColumn(
                'zettle_synced',
                __('PayPal Zettle Status', 'zettle-pos-integration'),
                $container->get('zettle.sync.status.matcher')
            );
        },
    'zettle.product.debug.cli.products' =>
        static function (C $container): ProductsCommand {
            return new ProductsCommand(
                $container->get('zettle.sync.allowed-product-types'),
                $container->get('zettle.sdk.id-map.product'),
                $container->get('zettle.sdk.builder'),
                $container->get('zettle.sdk.api.products'),
                $container->get('zettle.sync.validator.product'),
                $container->get('zettle.sync.status.matcher')
            );
        },
    'zettle.product.debug.namespace' => static function (): string {
        return 'zettle';
    },
    'zettle.product.debug.rest.namespace' => static function (C $container): string {
        $namespace = $container->get('zettle.product.debug.namespace');
        $validateEndpoint = $container->get('zettle.product.debug.rest.v1.endpoint.validate');

        return "{$namespace}-product-debug/{$validateEndpoint->version()}";
    },
    'zettle.product.debug.rest.v1.endpoint.validate' =>
        static function (C $container): EndpointInterface {
            return new ProductValidationEndpoint(
                $container->get('zettle.sync.validator.product')
            );
        },
    'zettle.product.debug.rest.v1.endpoint.validate.url' =>
        static function (C $container): string {
            $endpoint = $container->get('zettle.product.debug.rest.v1.endpoint.validate');

            return rest_url(
                $container->get('zettle.product.debug.rest.namespace') . $endpoint->route()
            );
        },
];
