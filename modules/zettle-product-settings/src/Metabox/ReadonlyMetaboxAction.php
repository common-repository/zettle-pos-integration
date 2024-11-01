<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\ProductSettings\Metabox;

use MetaboxOrchestra\AdminNotices;
use MetaboxOrchestra\BoxAction;

class ReadonlyMetaboxAction implements BoxAction
{

    /**
     * @inheritDoc
     */
    public function save(AdminNotices $notices): bool
    {
        return false;
    }
}
