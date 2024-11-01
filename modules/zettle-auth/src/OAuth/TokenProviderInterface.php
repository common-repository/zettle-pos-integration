<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Auth\OAuth;

use Inpsyde\Zettle\Auth\Exception\InvalidTokenException;
use Inpsyde\Zettle\Auth\OAuth\Token\TokenInterface;

interface TokenProviderInterface
{
    /**
     * @throws InvalidTokenException
     * @return TokenInterface
     */
    public function fetch(): TokenInterface;
}
