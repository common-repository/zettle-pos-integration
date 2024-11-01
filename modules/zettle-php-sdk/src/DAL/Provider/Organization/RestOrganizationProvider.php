<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\PhpSdk\DAL\Provider\Organization;

use Inpsyde\Zettle\PhpSdk\API\OAuth\Organizations;
use Inpsyde\Zettle\PhpSdk\DAL\Entity\Organization\Organization;
use Inpsyde\Zettle\PhpSdk\Exception\ZettleRestException;

class RestOrganizationProvider implements OrganizationProvider
{

    /**
     * @var Organizations
     */
    private $client;

    public function __construct(
        Organizations $client
    ) {

        $this->client = $client;
    }

    /**
     * @return Organization
     *
     * @throws ZettleRestException
     */
    public function provide(): Organization
    {
        /**
         * We don't handle the possible Exceptions,
         * if the organization can't be built, we have a problem
         */
        return $this->client->account();
    }
}
