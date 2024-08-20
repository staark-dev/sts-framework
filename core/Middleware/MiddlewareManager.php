<?php
declare(strict_types=1);
namespace STS\core\Middleware;

use STS\core\Container;
use STS\core\Http\Request;

class MiddlewareManager
{
    private array $middlewares = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function add($middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function handle(Request $request, callable $next = null): mixed
    {
        $middleware = array_shift($this->middlewares);

        if ($middleware === null) {
            return $next ? $next($request) : true;
        }

        // Dacă middleware-ul este un string, îl rezolvăm prin container
        if (is_string($middleware)) {
            $middleware = $this->container->make($middleware);
        }

        if (method_exists($middleware, 'handle')) {
            return $middleware->handle($request, function($request) use ($next) {
                return $this->handle($request, $next);
            });
        }

        return true;
    }
}
