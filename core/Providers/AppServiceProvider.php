<?php
namespace STS\core\Providers;

use STS\app\Controllers\HomeController;
use STS\core\Container;
use STS\core\Http\HttpKernel;
use STS\core\Routing\Router;
use STS\core\Themes\ThemeManager;
use STS\core\Modules\ModuleManager;
use STS\core\Plugins\PluginManager;
use STS\core\Events\EventManager;
use STS\core\Translation\Translation;
use STS\core\Session\SessionManager;
use STS\core\Cache\CacheManager;
use STS\core\Modules\PermissionManager;
use STS\core\Middleware\MiddlewareManager;
use STS\core\Database\Connection;
use STS\core\Http\Request;
use STS\core\Http\Response;

class AppServiceProvider {
    public static function register(Container $container, array $config): void
    {
        // Înregistrarea configurației
        $container->singleton('config', function() use ($config) {
            return $config;
        }, 0);

        // Înregistrarea serviciilor
        $container->singleton(HttpKernel::class, function($container) {
            return new HttpKernel($container);
        }, 100);

        // Înregistrează routerul în container
        // Înregistrează Router folosind metoda getInstance
        $container->singleton(\STS\core\Routing\Router::class, function($container) {
            return \STS\core\Routing\Router::getInstance();
        }, 100);

        // Înregistrează alte servicii necesare
        /*$container->singleton(AdminAuthMiddleware::class, function($container) {
            return new \App\Middleware\AdminAuthMiddleware();
        });*/

        $container->singleton(ThemeManager::class, function($container) {
            return new ThemeManager($container->make('config')['theme']);
        }, 0);

        $container->singleton(ModuleManager::class, function() {
            return new ModuleManager();
        }, 0);

        $container->singleton(PluginManager::class, function() {
            return new PluginManager();
        }, 0);

        $container->singleton(EventManager::class, function() {
            return new EventManager();
        }, 0);

        $container->singleton(Translation::class, function($container) {
            return new Translation($container->make('config')['locale']);
        }, 0);

        $container->singleton(SessionManager::class, function() {
            return new SessionManager();
        }, 0);

        $container->singleton(CacheManager::class, function() {
            return new CacheManager();
        }, 0);

        $container->singleton(PermissionManager::class, function() {
            return new PermissionManager();
        }, 0);

        $container->singleton(MiddlewareManager::class, function() {
            return new MiddlewareManager();
        }, 0);

        $container->singleton(Connection::class, function() {
            return new Connection();
        }, 5);

        // Înregistrarea request și response în container
        $container->singleton(Request::class, function() {
            return new Request();
        }, 10);

        $container->singleton(Response::class, function() {
            return new Response();
        }, 10);

        $container->singleton(HomeController::class, function($container) {
            return new \STS\app\Controllers\HomeController();
        }, 10);
    }
}