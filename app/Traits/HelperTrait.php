<?php
namespace App\Traits;

trait HelperTrait {
    protected function view($view, $data = []) {
        return $this->themeManager->display($view, $data);
    }

    protected function redirect($routeName, $parameters = []) {
        return $this->router->route($routeName, $parameters);
    }

    protected function withMessage($key, $message) {
        return $this->sessionManager->set($key, $message);
    }

    protected function getMessage($key) {
        return $this->sessionManager->get($key);
    }
}
