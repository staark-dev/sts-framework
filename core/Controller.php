<?php
namespace STS\core;

abstract class Controller {

    public $theme;
    protected $router;

    public function __construct() {
        $this->theme = app('theme');
    }

    protected function group(array $options, callable $callback) {
        $this->router->group($options, $callback);
    }

    protected function middleware($middleware) {
        $this->router->middleware($middleware);
    }

    public function view(string $view, ?string $title = '')
    {
        if(!empty($title))
            $this->theme->assign('page_title', app('globals')->get('page_title') . ' - ' . $title);

        return $this->theme->display($view);
    }
}
