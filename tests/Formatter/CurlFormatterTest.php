<?php

namespace Namshi\Cuzzle\Test\Formatter;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Namshi\Cuzzle\Formatter\CurlFormatter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CurlFormatterTest extends TestCase
{
    protected CurlFormatter $curlFormatter;

    public function setUp(): void
    {
        $this->curlFormatter = new CurlFormatter();
    }

    public function testMultiLineDisabled(): void
    {
        $this->curlFormatter->setCommandLineLength(10);

        $request = new Request('GET', 'http://example.local', ['foo' => 'bar']);
        $curl = $this->curlFormatter->format($request);

        $this->assertEquals(substr_count($curl, "\n"), 2);
    }

    public function testSkipHostInHeaders(): void
    {
        $request = new Request('GET', 'http://example.local');
        $curl = $this->curlFormatter->format($request);

        $this->assertEquals("curl 'http://example.local'", $curl);
    }

    public function testSimpleGET(): void
    {
        $request = new Request('GET', 'http://example.local');
        $curl = $this->curlFormatter->format($request);

        $this->assertEquals("curl 'http://example.local'", $curl);
    }

    public function testSimpleGETWithHeader(): void
    {
        $request = new Request('GET', 'http://example.local', ['foo' => 'bar']);
        $curl = $this->curlFormatter->format($request);

        $this->assertEquals("curl 'http://example.local' -H 'foo: bar'", $curl);
    }

    public function testSimpleGETWithMultipleHeader(): void
    {
        $request = new Request('GET', 'http://example.local', ['foo' => 'bar', 'Accept-Encoding' => 'gzip,deflate,sdch']);
        $curl = $this->curlFormatter->format($request);

        $this->assertEquals("curl 'http://example.local' -H 'foo: bar' -H 'Accept-Encoding: gzip,deflate,sdch'", $curl);
    }

    public function testGETWithQueryString(): void
    {
        $request = new Request('GET', 'http://example.local?foo=bar');
        $curl = $this->curlFormatter->format($request);

        $this->assertEquals("curl 'http://example.local?foo=bar'", $curl);

        $request = new Request('GET', 'http://example.local?foo=bar');
        $curl = $this->curlFormatter->format($request);

        $this->assertEquals("curl 'http://example.local?foo=bar'", $curl);

        $body = Utils::streamFor(http_build_query(['foo' => 'bar', 'hello' => 'world'], '', '&'));

        $request = new Request('GET', 'http://example.local', [], $body);
        $curl = $this->curlFormatter->format($request);

        $this->assertEquals("curl 'http://example.local' -G  -d 'foo=bar&hello=world'", $curl);
    }

    public function testPOST(): void
    {
        $body = Utils::streamFor(http_build_query(['foo' => 'bar', 'hello' => 'world'], '', '&'));

        $request = new Request('POST', 'http://example.local', [], $body);
        $curl = $this->curlFormatter->format($request);

        $this->assertStringContainsString("-d 'foo=bar&hello=world'", $curl);
        $this->assertStringNotContainsString(' -G ', $curl);
    }

    public function testHEAD(): void
    {
        $request = new Request('HEAD', 'http://example.local');
        $curl = $this->curlFormatter->format($request);

        $this->assertStringContainsString('--head', $curl);
    }

    public function testOPTIONS(): void
    {
        $request = new Request('OPTIONS', 'http://example.local');
        $curl = $this->curlFormatter->format($request);

        $this->assertStringContainsString('-X OPTIONS', $curl);
    }

    public function testDELETE(): void
    {
        $request = new Request('DELETE', 'http://example.local/users/4');
        $curl = $this->curlFormatter->format($request);

        $this->assertStringContainsString('-X DELETE', $curl);
    }

    public function testPUT(): void
    {
        $request = new Request('PUT', 'http://example.local', [], Utils::streamFor('foo=bar&hello=world'));
        $curl = $this->curlFormatter->format($request);

        $this->assertStringContainsString("-d 'foo=bar&hello=world'", $curl);
        $this->assertStringContainsString('-X PUT', $curl);
    }

    public function testProperBodyReading(): void
    {
        $request = new Request('PUT', 'http://example.local', [], Utils::streamFor('foo=bar&hello=world'));
        $request->getBody()->getContents();

        $curl = $this->curlFormatter->format($request);

        $this->assertStringContainsString("-d 'foo=bar&hello=world'", $curl);
        $this->assertStringContainsString('-X PUT', $curl);
    }

    #[Test]
    public function extractBodyArgument(): void
    {
        // clean input of null bytes
        $body = str_replace(chr(0), '', chr(0).'foo=bar&hello=world');
        $request = new Request('POST', 'http://example.local', ['X-Foo' => 'Bar'], Utils::streamFor($body));

        $curl = $this->curlFormatter->format($request);

        $this->assertStringContainsString('foo=bar&hello=world', $curl);
    }
}
