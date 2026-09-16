<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Nqphp\Core\Kernel\Kernel;
use Symfony\Component\HttpFoundation\Request;

$projectDir = dirname(__DIR__);
$kernel = new Kernel($projectDir);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
