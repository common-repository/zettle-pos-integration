<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\Auth\Jwt;

interface ParserFactoryInterface
{
    public function createParser(): ParserInterface;
}
