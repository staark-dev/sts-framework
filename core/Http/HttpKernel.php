<?php
namespace STS\core\Http;

use STS\core\Container;
use STS\core\Http\Request;
use STS\core\Http\Response;
use \ReflectionClass;
use \ReflectionMethod;

class HttpKernel {
    protected Container $container;
    protected array $middleware;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->middleware = $this->loadGlobalMiddleware();
    }

    protected function loadRoutes(): void {
        $routePath = sprintf("%s/routes/web.php", ROOT_PATH);
        if (file_exists($routePath)) {
            require_once $routePath;
        } else {
            throw new \Exception("Route file not found: $routePath");
        }
    }

    public function handle(?Request $request): Response {
        $this->loadRoutes();

        // Aplică middleware-urile globale
        $response = $this->applyGlobalMiddleware($request);
        if ($response instanceof Response) {
            return $response;
        }

        // Obține Router-ul din container
        $router = $this->container->make('Router');

        // Obține middleware-urile specifice rutei și le aplică
        $routeMiddleware = $router->getCurrentRouteMiddleware();
        $response = $this->applyRouteMiddleware($routeMiddleware, $request);
        if ($response instanceof Response) {
            return $response;
        }

        // Procesează cererea prin router și obține răspunsul
        $response = $router->dispatch($request);

        if (is_string($response)) {
            $response = new Response($response);
        }

        if ($response === null) {
            $response = new Response('', 404);
        }

        return $response;
    }

    protected function applyGlobalMiddleware(Request $request): ?Response {
        foreach ($this->middleware as $middlewareClass) {
            $middlewareInstance = $this->container->make($middlewareClass);

            // Verifică dacă middleware-ul este valid
            $this->validateMiddleware($middlewareInstance);

            $response = $middlewareInstance->handle($request, function ($req) {
                return null;
            });

            if ($response instanceof Response) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Verifică dacă un middleware îndeplinește condițiile predefinite.
     *
     * @param object $middleware
     * @throws \Exception
     */
    protected function validateMiddleware(object $middleware): void {
        $reflection = new ReflectionClass($middleware);

        // Verifică dacă middleware-ul implementează interfața MiddlewareInterface
        if (!$reflection->implementsInterface(MiddlewareInterface::class)) {
            throw new \Exception("Middleware " . $reflection->getName() . " must implement MiddlewareInterface.");
        }

        // Verifică dacă metoda 'handle' este definită corect
        try {
            $method = $reflection->getMethod('handle');

            // Verifică semnătura metodei 'handle'
            if (!$this->isHandleMethodValid($method)) {
                throw new \Exception("Middleware " . $reflection->getName() . " must define a 'handle' method with the correct signature.");
            }
        } catch (\ReflectionException $e) {
            throw new \Exception("Middleware " . $reflection->getName() . " must define a 'handle' method.");
        }
    }

    /**
     * Verifică semnătura metodei 'handle'.
     *
     * @param ReflectionMethod $method
     * @return bool
     */
    protected function isHandleMethodValid(ReflectionMethod $method): bool {
        // Verifică dacă metoda 'handle' are cel puțin doi parametri
        if ($method->getNumberOfParameters() < 2) {
            return false;
        }

        // Verifică tipurile parametrilor
        $parameters = $method->getParameters();
        $firstParamType = $parameters[0]->getType();
        $secondParamType = $parameters[1]->getType();

        if (
            $firstParamType && $firstParamType->getName() === Request::class &&
            $secondParamType && $secondParamType->getName() === 'callable'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Încarcă middleware-urile globale din fișierul de configurare.
     *
     * @return array
     */
    protected function loadGlobalMiddleware(): array {
        $configPath = sprintf("%s/config/middleware.php", ROOT_PATH);

        if (file_exists($configPath)) {
            return require $configPath;
        } else {
            throw new \Exception("Middleware config file not found: $configPath");
        }
    }
}
