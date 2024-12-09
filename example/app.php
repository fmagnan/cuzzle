<?php

require '../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleToCurlConverter\Subscriber\CurlFormatterSubscriber;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

$logger = new Logger('guzzele.to.curl'); // initialize the logger
$testHandler = new TestHandler(); // test logger handler
$logger->pushHandler($testHandler);

$client = new Client(); // initialize a Guzzle client
$client->getEmitter()->attach(new CurlFormatterSubscriber($logger)); // add the cURL formatter subscriber

$response = $client->get('http://google.com'); // let's fire a request

var_dump($testHandler->getRecords()); // check the cURL request in the logs :)
