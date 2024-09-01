<?php
namespace STS\core\Http;

use STS\core\Container;
use STS\core\Http\Request;
use STS\core\Http\Response;

class HttpKernel {
    protected Container $container;

    public function __construct(Container $container) {
        // Inițializează proprietatea tipizată în constructor
        $this->container = $container;
    }

    protected function loadRoutes(): void {
        // Verifică dacă fișierul de rute există
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
    public function handle(?Request $request): Response {
        $this->loadRoutes();

        // Obține Router-ul din container
        $router = $this->container->make('Router');

        // Procesează cererea prin router și obține răspunsul
        $response = $router->dispatch($request);

        // Dacă răspunsul este un string, creează un obiect Response
        if (is_string($response)) {
            $response = new Response($response);
        }

        // Dacă nu există un răspuns valid, returnează un răspuns 404
        if ($response === null) {
            $response = new Response('', 404);
        }

        return $response;
    }
}