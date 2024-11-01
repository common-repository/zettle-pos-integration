<?php

declare(strict_types=1);

namespace Inpsyde\Wp\HttpClient\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * General Http Client exception.
 *
 * It thrown where it's unable to send request or parse response.
 */
class WpHttpClientException extends RuntimeException implements ClientExceptionInterface
{

}
