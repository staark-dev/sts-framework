<?php
namespace STS\core;

use STS\core\Facades\Theme;
use STS\core\Routing\Router;
use STS\core\Facades\Globals;
use STS\core\Http\Request;
use STS\core\Http\MiddlewareRegistry;

abstract class Controller {
    // Definire router-ului
    protected Router $router;

    // Definire acțiuni specifice și middleware-uri
    protected array $middleware = [];
    protected array $middlewareForActions = [];

    public function __construct()
    {
        $this->router = Router::getInstance(); // Obtine instanta router-ului
        $this->applyMiddleware(); // Aplică middleware-urile pentru actiunea curentă
    }

    protected function applyMiddleware(): void
    {
        // Aplica middleware-urile implicite
        foreach ($this->middleware as $middleware) {
            if (is_string($middleware) && $this->middlewareExists($middleware)) {
                // Middleware este un string și există
                $this->middleware($middleware);
            } elseif (is_array($middleware)) {
                // Middleware este un array - aplică fiecare middleware individual
                foreach ($middleware as $singleMiddleware) {
                    if (is_string($singleMiddleware) && $this->middlewareExists($singleMiddleware)) {
                        $this->middleware($singleMiddleware);
                    }
                }
            }
        }
    
        // Aplica middleware-urile pentru acțiuni specifice
        foreach ($this->middlewareForActions as $action => $middlewares) {
            if ($this->isCurrentAction($action)) {
                foreach ($middlewares as $middleware) {
                    if (is_string($middleware) && $this->middlewareExists($middleware)) {
                        $this->middleware($middleware);
                    } elseif (is_array($middleware)) {
                        // Middleware este un array - aplică fiecare middleware individual
                        foreach ($middleware as $singleMiddleware) {
                            if (is_string($singleMiddleware) && $this->middlewareExists($singleMiddleware)) {
                                $this->middleware($singleMiddleware);
                            }
                        }
                    }
                }
            }
        }
    }

    /*protected function applyMiddleware(): void
    {
        // Aplica middleware-urile implicite
        foreach ($this->middleware as $middleware) {
            if (is_string($middleware) && $this->middlewareExists($middleware)) {
                $this->middleware($middleware);
            } elseif (isset($this->middlewareGroups[$middleware])) {
                // Aplica grupul de middleware-uri
                foreach ($this->middlewareGroups[$middleware] as $groupMiddleware) {
                    if ($this->middlewareExists($groupMiddleware)) {
                        $this->middleware($groupMiddleware);
                    }
                }
            }
        }
    
        // Aplica middleware-urile pentru acțiuni specifice
        foreach ($this->middlewareForActions as $action => $middlewares) {
            if ($this->isCurrentAction($action)) {
                foreach ($middlewares as $middleware) {
                    if (is_string($middleware) && $this->middlewareExists($middleware)) {
                        $this->middleware($middleware);
                    } elseif (isset($this->middlewareGroups[$middleware])) {
                        // Aplica grupul de middleware-uri
                        foreach ($this->middlewareGroups[$middleware] as $groupMiddleware) {
                            if ($this->middlewareExists($groupMiddleware)) {
                                $this->middleware($groupMiddleware);
                            }
                        }
                    }
                }
            }
        }
    }*/
    
    // Metoda pentru a obține middleware-urile
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    // Metoda pentru a obține middleware-urile pentru acțiuni specifice
    public function getMiddlewareForActions(): array
    {
        return $this->middlewareForActions;
    }

    protected function middleware(string|array $middleware): void
    {
        // Aplică middleware-ul folosind router-ul
        $this->router->middleware($middleware);
    }

    protected function middlewareExists(string $middleware): bool
    {
        // Verifică dacă middleware-ul este înregistrat
        return MiddlewareRegistry::exists($middleware);
    }

    protected function isCurrentAction(string $action): bool
    {
        $currentAction = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? null;
        return $currentAction === $action;
    }
    
    protected function group(array $options, callable $callback): Router {
        $this->router->group($options, $callback);
    }

    public function view(string $template, ?string $title = '', ?array $errors = []): mixed
    {
        if(!empty($title))
            Globals::set('page_title', Globals::get('page_title') . ' - ' . $title);

        $data = [
            'title' => $title,
            'errors' => $errors, // Transmite erorile către view
        ];

        return Theme::display($template, $data);
    }
}
