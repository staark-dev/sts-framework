<?php
declare(strict_types=1);

namespace STS\core\Routing;

use STS\core\Container;

class Route {
    protected string $method;
    protected string $uri;
    protected $action;
    protected ?string $name = null;
    protected array $middleware = [];

    public function __construct(string $method, string $uri, $action) {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }

    public function name(string $name): self {
        $this->name = ''.$name.'';
        $y = Container::getInstance()->make(Router::class);
    
        //Router::getInstance()->namedRoutes[$name] = $this;
        return $this;
    }

    public function middleware(string ...$middleware): self {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getMiddleware(): array {
        return $this->middleware;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function getAction() {
        return $this->action;
    }
}