<?php
namespace App\Controllers;

use App\Traits\HelperTrait;

abstract class BaseController {
    use HelperTrait;

    public $themeManager;
    protected $sessionManager;
    protected $router;

    public function __construct() {
        $this->themeManager = new \STS\core\Themes\ThemeManager();
    }

    protected function group(array $options, callable $callback) {
        $this->router->group($options, $callback);
    }

    protected function middleware($middleware) {
        $this->router->middleware($middleware);
    }
}
