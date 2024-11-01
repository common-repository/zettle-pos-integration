<?php

namespace Inpsyde\Zettle\PhpSdk\DAL\Provider\Organization;

use Inpsyde\Zettle\PhpSdk\DAL\Entity\Organization\Organization;
use Inpsyde\Zettle\PhpSdk\Exception\ZettleRestException;

interface OrganizationProvider
{

    /**
     * @return Organization
     * @throws ZettleRestException
     */
    public function provide(): Organization;
}
