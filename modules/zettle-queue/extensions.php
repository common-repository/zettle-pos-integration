<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Queue;

use Psr\Container\ContainerInterface as C;

return [
    'inpsyde.queue.namespace' => static function (C $container, string $previous): string {
        return "zettle";
    },
];
