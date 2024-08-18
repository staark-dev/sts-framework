<?php
namespace App\Providers;

use STS\core\{
    Container,
    Themes\ThemeManager, 
    ConfigManager,
    Routing\Router,
    Http\HttpKernel,
    Http\Response,
    Http\Request,
    Middleware\MiddlewareManager,
    Auth\Auth
};

class AppServiceProvider {
    public static function register(Container $container) {
        // Înregistrarea ConfigManager
        $container->singleton(ConfigManager::class, function() {
            $config = [];
            foreach (glob(ROOT_PATH . '/config/*.php') as $file) {
                $key = basename($file, '.php');
                $config[$key] = require $file;
            }

            return new ConfigManager($config);
        }, 0);

        $container->singleton(ThemeManager::class, function($container) {
            $configManager = $container->make(ConfigManager::class);
            return new ThemeManager($configManager);
        }, 90);

        $container->singleton(Router::class, function() {
            return Router::getInstance();
        }, 0);

        $container->singleton(MiddlewareManager::class, function() {
            return new MiddlewareManager();
        }, 0);

        // Inițializează Request cu datele din cererea curentă
        $container->singleton(Request::class, function() {
            return Request::capture(); 
        }, 10);

        $container->singleton(Response::class, function() {
            return new Response();
        }, 10);

        $container->singleton(HttpKernel::class, function($container) {
            // Înregistrarea HttpKernel în container
            $container->singleton(HttpKernel::class, function($container) {
                return new HttpKernel($container);
            });
        }, 95);

        $container->singleton('Auth', function($container) {
            return new Auth();
        }, 50);
        
        $container->singleton('AdminAuthMiddleware', function($container) {
            return new \App\Middleware\AdminAuthMiddleware();
        }, 30);

        $container->singleton('SetThemeMiddleware', function($container) {
            $theme = $container->make(ThemeManager::class);
            return new \App\Middleware\SetThemeMiddleware($theme);
        }, 30);
    }
}
