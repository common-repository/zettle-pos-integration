<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Notices\Notice;

interface NoticeInterface
{
    /**
     * @param string $currentState
     *
     * @return bool
     */
    public function accepts(string $currentState): bool;

    /**
     * @return string
     */
    public function render(): string;
}
