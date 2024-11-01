<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\ProductSettings\Provider;

use Inpsyde\Zettle\ProductSettings\Taxonomy\ZettleSyncVisibilityTaxonomy;
use Inpsyde\Zettle\Provider;
use Psr\Container\ContainerInterface as C;

class SyncVisibilityTaxonomyProvider implements Provider
{

    /**
     * @var ZettleSyncVisibilityTaxonomy
     */
    private $syncVisibilityTaxonomy;

    public function __construct(ZettleSyncVisibilityTaxonomy $syncVisibilityTaxonomy)
    {
        $this->syncVisibilityTaxonomy = $syncVisibilityTaxonomy;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_action(
            'init',
            [$this->syncVisibilityTaxonomy, 'create']
        );

        return true;
    }
}
