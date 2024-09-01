<?php
namespace STS\core;

use STS\core\Facades\Theme;
use STS\core\Facades\Globals;

abstract class Controller {

    public $theme;
    protected $router;

    protected function group(array $options, callable $callback) {
        $this->router->group($options, $callback);
    }

    protected function middleware($middleware) {
        $this->router->middleware($middleware);
    }

    public function view(string $template, ?string $title = '', ?array $errors = []): mixed
    {
        if(!empty($title))
            Globals::set('page_title', Globals::get('page_title') . ' - ' . $title);

        Globals::set('app_name', env('APP_NAME', 'STS Framework'));

        $data = [
            'title' => $title,
            'errors' => $errors, // Transmite erorile către view
        ];

        return Theme::display($template, $data);
    }
}
