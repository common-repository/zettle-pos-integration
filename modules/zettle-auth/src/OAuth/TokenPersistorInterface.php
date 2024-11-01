<?php

namespace Inpsyde\Zettle\Auth\OAuth;

use Inpsyde\Zettle\Auth\OAuth\Token\TokenInterface;

interface TokenPersistorInterface
{

    public function persist(TokenInterface $token): bool;

    public function clear(): bool;
}
