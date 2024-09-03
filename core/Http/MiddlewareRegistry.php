<?php
namespace STS\core\Http;

class MiddlewareRegistry
{
    protected static array $middlewares = [
        'auth' => \STS\core\Http\Middleware\AuthMiddleware::class, // Verifică că această referință este corectă
        'check_permission' => \STS\core\Http\Middleware\PermissionMiddleware::class,
        'permission' => \STS\core\Http\Middleware\PermissionMiddleware::class,
        'admin_only' => \STS\core\Http\Middleware\AdminOnlyMiddleware::class,
        'role' => \STS\core\Http\Middleware\RoleMiddleware::class,
    ];

    public static function register(string $name, string $middlewareClass): void {
        self::$middlewares[$name] = $middlewareClass;
    }

    public static function getMiddlewareInstance(string $name)
    {
        if (!isset(self::$middlewares[$name])) {
            throw new \Exception("Middleware not found: $name");
        }

        $middlewareClass = self::$middlewares[$name];
        
        // Creează și returnează instanța middleware-ului
        return app()->make($middlewareClass);
    }

    public static function get(string $name): ?string
    {
        return self::$middlewares[$name] ?? null;
    }

    public static function exists(string $name): bool
    {
        return isset(self::$middlewares[$name]);
    }
}