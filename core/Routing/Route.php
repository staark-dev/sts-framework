<?php
declare(strict_types=1);

namespace STS\core\Routing;

class Route {
    protected string $method;
    protected string $uri;
    protected $action;
    protected ?string $name = null;
    protected array $middleware = [];
    protected array $middlewares = []; // Inițializează proprietatea ca un array gol

    public function __construct(string $method, string $uri, $action, ?array $middleware = []) {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
        $this->middleware = $middleware ?? [];
    }

    public function name(string $name): self {
        $this->name = ''.$name.'';
        Router::getInstance()->namedRoutes[$name] = $this;
        return $this;
    }

    /**
     * Adaugă middleware-uri la această rută.
     *
     * @param string ...$middleware
     * @return self
     */
    public function middleware(string ...$middleware): self {
        $this->middlewares = array_merge($this->middlewares, $middleware);
        return $this;
    }
    
    /**
     * Returnează middleware-urile asociate cu această rută.
     *
     * @return array
     */
    public function getMiddleware(): array {
        return $this->middlewares;
    }

    /**
     * Returnează acțiunea asociată cu această rută.
     *
     * @return mixed
     */
    public function getAction() {
        return $this->action;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getUri(): string {
        return $this->uri;
    }
}