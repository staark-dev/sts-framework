<?php
namespace App\Providers;

use STS\core\Container;
use STS\core\Session\CustomSessionHandler;

class SessionServiceProvider {
    public static function register(Container $container) {
        $container->singleton('session.handler', function($container) {
            return new CustomSessionHandler();
        }, 0);

        // Configurarea handler-ului personalizat pentru sesiuni
        session_set_save_handler($container->make('session.handler'), true);

        /*
        // Înregistrarea managerului de sesiuni
        $container->singleton(SessionManager::class, function($container) {
            $config = $container->make('config')->get('session');
            return new SessionManager($config);
        }, 75);

        // Înregistrarea handler-ului de sesiuni personalizat, dacă este cazul
        $container->singleton('session.handler', function($container) {
            // Presupunem că există un serviciu PDO în container
            $pdo = $container->make(Connection::class);
            return new CustomSessionHandler($pdo);
        }, 80);*/
    }

    public function boot() {

    }
}
