<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\API\Inventory;

use Inpsyde\Zettle\PhpSdk\Builder\BuilderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Location\Location;
use Inpsyde\Zettle\PhpSdk\Exception\BuilderException;
use Inpsyde\Zettle\PhpSdk\Exception\ZettleRestException;
use Inpsyde\Zettle\PhpSdk\RestClientInterface;
use Psr\Http\Message\UriInterface;

class Locations
{

    private $uri;

    /**
     * @var RestClientInterface
     */
    private $restClient;

    /**
     * @var BuilderInterface
     */
    private $builder;

    public function __construct(
        UriInterface $uri,
        RestClientInterface $restClient,
        BuilderInterface $builder
    ) {

        $this->uri = $uri;
        $this->restClient = $restClient;
        $this->builder = $builder;
    }

    /**
     * @return Location[]
     * @throws ZettleRestException
     */
    public function all(): array
    {
        $url = (string) $this->uri->withPath('/v3/inventories');

        $result = $this->restClient->get($url, []);
        $locations = [];
        foreach ($result as $locationPayload) {
            try {
                $locations[$locationPayload['inventoryType']] = $this->builder->build(Location::class, $locationPayload);
            } catch (BuilderException $exception) {
                // TODO may wanna log, but an error is pretty unlikely to occur here
            }
        }

        return $locations;
    }
}
