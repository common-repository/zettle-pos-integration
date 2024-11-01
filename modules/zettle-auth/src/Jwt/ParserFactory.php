<?php

namespace Inpsyde\Zettle\Auth\Jwt;

class ParserFactory implements ParserFactoryInterface
{
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function createParser(): ParserInterface
    {
        return new Parser();
    }
}
