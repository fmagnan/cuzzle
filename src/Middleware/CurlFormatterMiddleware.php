<?php

namespace GuzzleToCurlConverter\Middleware;

use GuzzleToCurlConverter\Formatter\CurlFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CurlFormatterMiddleware middleware
 * it allows to attach the CurlFormatter to a Guzzle Request.
 */
class CurlFormatterMiddleware
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $curlCommand = (new CurlFormatter())->format($request, $options);
            $this->logger->debug($curlCommand);

            return $handler($request, $options);
        };
    }
}
