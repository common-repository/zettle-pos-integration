<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Auth\OAuth;

use Inpsyde\Zettle\Auth\OAuth\Token\TokenFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class TokenPersistingAuthSuccessHandler implements AuthSuccessHandler
{

    /**
     * @var TokenPersistorInterface
     */
    private $tokenPersistor;

    /**
     * @var TokenFactoryInterface
     */
    private $tokenFactory;

    public function __construct(
        TokenPersistorInterface $tokenPersistor,
        TokenFactoryInterface $tokenFactory
    ) {
        $this->tokenPersistor = $tokenPersistor;
        $this->tokenFactory = $tokenFactory;
    }

    public function handle(ResponseInterface $response)
    {
        $body = $response->getBody();
        $body->rewind();

        $contents = $body->getContents();
        $json = json_decode($contents, true);
        $token = $this->tokenFactory->fromArray($json);
        $this->tokenPersistor->persist($token);
    }
}
