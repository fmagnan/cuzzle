# Guzzle to cURL converter, generates cURL command from Guzzle requests

[![Build Status](https://travis-ci.org/fmagnan/guzzle-to-curl-converter.svg?branch=master)](https://travis-ci.org/fmagnan/guzzle-to-curl-converter)

This library lets you dump a Guzzle request to a cURL command for debug and log purpose.

## Prerequisites

This library needs PHP 8.3+.

It has been tested using PHP8.3

## Installation

You can install the library directly with composer:

```
composer require fmagnan/guzzle-to-curl-converter
```

(Add `--dev` if you don't need it in production environment)

## Usage

```php

use GuzzleToCurlConverter\Formatter\CurlFormatter;
use GuzzleHttp\Message\Request;

$request = new Request('GET', 'example.local');
$options = [];

echo (new CurlFormatter())->format($request, $options);

```

To log the cURL request generated from a Guzzle request, simply add CurlFormatterSubscriber to Guzzle:

```php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleToCurlConverter\Middleware\CurlFormatterMiddleware;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

$logger = new Logger('guzzle.to.curl'); //initialize the logger
$testHandler = new TestHandler(); //test logger handler
$logger->pushHandler($testHandler);

$handler = HandlerStack::create();
$handler->after('cookies', new CurlFormatterMiddleware($logger)); //add the cURL formatter middleware
$client  = new Client(['handler' => $handler]); //initialize a Guzzle client

$response = $client->get('http://google.com'); //let's fire a request

var_dump($testHandler->getRecords()); //check the cURL request in the logs,
//you should see something like: "curl 'http://google.com' -H 'User-Agent: Guzzle/4.2.1 curl/7.37.1 PHP/5.5.16"

```

## Tests

You can run tests locally with

```
bin/phpunit
```

## Feedback

Add an issue, open a PR, drop us an email! We would love to hear from you!
