<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Auth\OAuth\Grant;

use Inpsyde\Zettle\Auth\Exception\InvalidTokenException;

interface GrantType
{

    /**
     * @return string
     */
    public function type(): string;

    /**
     * @return array
     * @throws InvalidTokenException
     */
    public function payload(): array;
}
