<?php
namespace Test\One;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv;
$dotenv->load(__DIR__ . '/../.env');

$kernel = new Kernel(getenv('DATABASE_URL'));

/** @var Request */
$request = Request::createFromGlobals();
$response = $kernel->handle($request);

$response->send();
