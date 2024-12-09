<?php

namespace GuzzleToCurlConverter\Test\Middleware;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleToCurlConverter\Middleware\CurlFormatterMiddleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CurlFormatterMiddlewareTest extends TestCase
{
    #[Test]
    public function get(): void
    {
        $mock = new MockHandler([new Response(204)]);
        $handler = HandlerStack::create($mock);
        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringStartsWith('curl'));

        $handler->after('cookies', new CurlFormatterMiddleware($logger));
        $client = new Client(['handler' => $handler]);

        $client->get('https://google.com');
    }
}
