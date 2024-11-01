<?php

namespace Inpsyde\Zettle\Auth\OAuth;

use Psr\Http\Message\ResponseInterface;

interface AuthSuccessHandler
{

    public function handle(ResponseInterface $response);
}
