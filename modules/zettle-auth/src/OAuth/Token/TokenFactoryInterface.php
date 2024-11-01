<?php

namespace Inpsyde\Zettle\Auth\OAuth\Token;

interface TokenFactoryInterface
{

    public function fromArray(array $data): TokenInterface;
}
