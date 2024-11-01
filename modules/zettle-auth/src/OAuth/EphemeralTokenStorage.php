<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Auth\OAuth;

use Inpsyde\Zettle\Auth\Exception\InvalidTokenException;
use Inpsyde\Zettle\Auth\OAuth\Token\TokenInterface;

/**
 * Class EphemeralTokenStorage
 * Stores tokens only for the lifetime of the current request/process.
 * This is probably only useful for running acceptance tests
 *
 * @package Inpsyde\Zettle\Auth\OAuth
 */
class EphemeralTokenStorage implements TokenProviderInterface, TokenPersistorInterface
{

    /**
     * @var TokenInterface|null
     */
    private $token;

    /**
     * @param TokenInterface $token
     *
     * @return bool
     */
    public function persist(TokenInterface $token): bool
    {
        $this->token = $token;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetch(): TokenInterface
    {
        if (!($this->token instanceof TokenInterface)) {
            throw new InvalidTokenException("No token found in the database");
        }

        return $this->token;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return empty($this->token);
    }

    public function clear(): bool
    {
        $this->token = null;

        return true;
    }
}
