<?php
namespace Test\One;

use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

$kernel = new Kernel;

/** @var Request */
$request = Request::createFromGlobals();
$response = $kernel->handle($request);

$response->send();
