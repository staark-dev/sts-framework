<?php
define('ROOT_PATH', dirname(__DIR__));

require_once __DIR__ . '/../vendor/autoload.php';

use STS\core\Container;
use STS\core\Providers\App\AppServiceProvider;
use STS\core\Http\HttpKernel;
use STS\core\Http\Request;

$container = Container::getInstance();

$provider = new AppServiceProvider($container);
$provider->register();
$provider->boot();

$request = Request::capture();

$kernel = $container->make(HttpKernel::class);

$response = $kernel->handle($request);

$response->send();