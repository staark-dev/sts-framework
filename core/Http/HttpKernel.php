<?php

namespace STS\core\Http;

use STS\core\{
    Container,
    Http\Request,
    Routing\Router
};

class HttpKernel {
    protected Container $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function loadRoutes(): void
    {
        // Verifică dacă fișierul există pentru a preveni erorile
        $routePath = sprintf("%s/routes/web.php", ROOT_PATH);
        if (file_exists($routePath)) {
            require_once $routePath;
        } else {
            throw new \Exception("Route file not found: $routePath");
        }
    }

    /**
     * @throws \Exception
     */
    public function handle(Request $request): Response
    {
        $this->loadRoutes();
        
        // Folosește Router-ul din container
        $router = $this->container->make(Router::class);

        // Procesează cererea prin router și obține răspunsul
        $response = $router->dispatch($request);

        // Dacă dispatch returnează un string, înfășoară-l într-un obiect Response
        if (is_string($response)) {
            $response = new Response($response);
        }

        // Dacă nu există un răspuns valid, generează o eroare sau un răspuns implicit
        if ($response === null) {
            $response = new Response('Page not found', 404);
        }
        
        return $response;
    }
}