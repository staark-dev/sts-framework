<?php
define('ROOT_PATH', dirname(__DIR__));

require_once __DIR__ . '/../vendor/autoload.php';
use STS\core\Container;
use STS\core\Providers\App\AppServiceProvider;
use STS\core\Http\HttpKernel;
use STS\core\Http\Request;
use STS\core\Facades\Kernel;

$provider = new AppServiceProvider(Container::getInstance());
$provider->register();
$provider->boot();

// Routing and handling request
$response = Kernel::handle(Request::collection());
$response->send();