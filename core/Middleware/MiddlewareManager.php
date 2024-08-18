<?php
declare(strict_types=1);
namespace STS\core\Middleware;

class MiddlewareManager
{
    private array $middlewares = [];

    public function add($middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function handle($request): bool
    {
        foreach ($this->middlewares as $middleware) {
            if (!$middleware->handle($request)) {
                return false;
            }
        }
        return true;
    }
}