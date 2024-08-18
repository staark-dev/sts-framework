<?php
define("ROOT_PATH", trim(dirname(__DIR__) . DIRECTORY_SEPARATOR, '\\/'));
require_once ROOT_PATH . '/bootstrap/app.php';

use STS\core\App;

// Rulează aplicația
$app = new App($container);
$app->run();