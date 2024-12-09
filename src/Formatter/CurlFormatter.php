<?php

namespace Namshi\Cuzzle\Formatter;

use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\RequestInterface;

/**
 * Class CurlFormatter it formats a Guzzle request to a cURL shell command
 * @package Namshi\Cuzzle\Formatter
 */
class CurlFormatter
{
    protected string $command;

    protected int $currentLineLength;

    protected array $options;

    protected int $commandLineLength;

    function __construct(int $commandLineLength = 100)
    {
        $this->commandLineLength = $commandLineLength;
    }

    public function format(RequestInterface $request, array $options = []): string
    {
        $this->command = 'curl';
        $this->currentLineLength = strlen($this->command);
        $this->options = [];

        $this->extractArguments($request, $options);
        $this->addOptionsToCommand();

        return $this->command;
    }

    public function setCommandLineLength(int $commandLineLength): self
    {
        $this->commandLineLength = $commandLineLength;

        return $this;
    }

    protected function addOption(string $name, mixed $value = null): self
    {
        if (isset($this->options[$name])) {
            if (!is_array($this->options[$name])) {
                $this->options[$name] = (array)$this->options[$name];
            }
            $this->options[$name][] = $value;
        } else {
            $this->options[$name] = $value;
        }

        return $this;
    }

    protected function addCommandPart(string $part): self
    {
        $this->command .= ' ';

        if ($this->commandLineLength > 0 && $this->currentLineLength + strlen($part) > $this->commandLineLength) {
            $this->currentLineLength = 0;
            $this->command .= "\\\n  ";
        }

        $this->command .= $part;
        $this->currentLineLength += strlen($part) + 2;

        return $this;
    }

    protected function extractHttpMethodArgument(RequestInterface $request): self
    {
        if ('GET' !== $request->getMethod()) {
            if ('HEAD' === $request->getMethod()) {
                $this->addOption('-head');
            } else {
                $this->addOption('X', $request->getMethod());
            }
        }

        return $this;
    }

    protected function extractBodyArgument(RequestInterface $request): self
    {
        $body = $request->getBody();

        if ($body->isSeekable()) {
            $previousPosition = $body->tell();
            $body->rewind();
        }

        $contents = $body->getContents();

        if ($body->isSeekable()) {
            $body->seek($previousPosition);
        }

        if ($contents) {
            // clean input of null bytes
            $contents = str_replace(chr(0), '', $contents);
            $this->addOption('d', escapeshellarg($contents));
        }

        //if get request has data Add G otherwise curl will make a post request
        if (!empty($this->options['d']) && ('GET' === $request->getMethod())) {
            $this->addOption('G');
        }

        return $this;
    }

    protected function extractCookiesArgument(RequestInterface $request, array $options): self
    {
        if (!isset($options['cookies']) || !$options['cookies'] instanceof CookieJarInterface) {
            return $this;
        }

        $values = [];
        $scheme = $request->getUri()->getScheme();
        $host = $request->getUri()->getHost();
        $path = $request->getUri()->getPath();

        /** @var SetCookie $cookie */
        foreach ($options['cookies'] as $cookie) {
            if ($cookie->matchesPath($path) && $cookie->matchesDomain($host) &&
                !$cookie->isExpired() && (!$cookie->getSecure() || $scheme == 'https')) {

                $values[] = $cookie->getName() . '=' . $cookie->getValue();
            }
        }

        if ($values) {
            $this->addOption('b', escapeshellarg(implode('; ', $values)));
        }

        return $this;
    }

    protected function extractHeadersArgument(RequestInterface $request): self
    {
        foreach ($request->getHeaders() as $name => $header) {
            if ('host' === strtolower($name) && $header[0] === $request->getUri()->getHost()) {
                continue;
            }

            if ('user-agent' === strtolower($name)) {
                $this->addOption('A', escapeshellarg($header[0]));
                continue;
            }

            foreach ((array)$header as $headerValue) {
                $this->addOption('H', escapeshellarg("{$name}: {$headerValue}"));
            }
        }

        return $this;
    }

    protected function addOptionsToCommand(): self
    {
        ksort($this->options);

        if ($this->options) {
            foreach ($this->options as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $subValue) {
                        $this->addCommandPart("-{$name} {$subValue}");
                    }
                } else {
                    $this->addCommandPart("-{$name} {$value}");
                }
            }
        }

        return $this;
    }

    protected function extractArguments(RequestInterface $request, array $options): self
    {
        $this->extractHttpMethodArgument($request);
        $this->extractBodyArgument($request);
        $this->extractCookiesArgument($request, $options);
        $this->extractHeadersArgument($request);
        $this->extractUrlArgument($request);

        return $this;
    }

    protected function extractUrlArgument(RequestInterface $request) : self
    {
        return $this->addCommandPart(escapeshellarg((string)$request->getUri()->withFragment('')));
    }
}
