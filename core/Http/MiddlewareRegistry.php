<?php

namespace STS\core\Http;

class MiddlewareRegistry
{
    protected static array $middlewares = [
        'auth' => \STS\core\Http\Middleware\AuthMiddleware::class,
        'check_permission' => \STS\core\Http\Middleware\CheckPermissionMiddleware::class,
        'admin_only' => \STS\core\Http\Middleware\AdminOnlyMiddleware::class,
        'role' => \STS\core\Http\Middleware\RoleMiddleware::class,
        // Utilizatorii pot adăuga aici middleware-urile lor personalizate...
    ];

    /**
     * Înregistrează middleware-urile din fișierul de configurare.
     */
    public static function registerConfigMiddleware(): void {
        $middlewareConfig = require sprintf("%s/config/middleware.php", ROOT_PATH);

        foreach ($middlewareConfig as $middlewareClass) {
            self::register($middlewareClass, $middlewareClass);
        }
    }

    /**
     * Înregistrează un nou middleware.
     *
     * @param string $name
     * @param string $middlewareClass
     * @return void
     */
    public static function register(string $name, string $middlewareClass): void {
        self::$middlewares[$name] = $middlewareClass;
    }

    /**
     * Obține o instanță a middleware-ului după nume.
     *
     * @param string $name
     * @return string
     * @throws \Exception
     */
    public static function getMiddlewareInstance(string $name): string
    {
        if (!isset(self::$middlewares[$name])) {
            throw new \Exception("Middleware not found: $name");
        }

        return self::$middlewares[$name];
    }

    /**
     * Returnează clasa middleware-ului.
     *
     * @param string $name
     * @return string|null
     */
    public static function get(string $name): ?string
    {
        return self::$middlewares[$name] ?? null;
    }

    /**
     * Verifică dacă middleware-ul există.
     *
     * @param string $name
     * @return bool
     */
    public static function exists(string $name): bool
    {
        return isset(self::$middlewares[$name]);
    }
}
