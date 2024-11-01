<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\API\OAuth;

use Inpsyde\Zettle\PhpSdk\Builder\BuilderInterface;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Organization\Organization;
use Inpsyde\Zettle\PhpSdk\Exception\BuilderException;
use Inpsyde\Zettle\PhpSdk\Exception\ZettleRestException;
use Inpsyde\Zettle\PhpSdk\RestClientInterface;
use Psr\Http\Message\UriInterface;

class Organizations
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

    /**
     * Organizations constructor.
     *
     * @param UriInterface $uri
     * @param RestClientInterface $restClient
     * @param BuilderInterface $builder
     */
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
     * @return Organization
     *
     * @throws ZettleRestException
     */
    public function account(): Organization
    {
        $url = (string) $this->uri->withPath('/api/resources/organizations/self');

        $result = $this->restClient->get($url, []);

        try {
            return $this->builder->build(
                Organization::class,
                $result
            );
        } catch (BuilderException $exception) {
            throw new ZettleRestException(
                sprintf('Failed to build Organization entity after fetching'),
                0,
                [],
                [],
                $exception
            );
        }
    }
}
