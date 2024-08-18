<?php

namespace STS\core;
use STS\core\{
    Routing\Router,
    Container,
    Http\HttpKernel,
    Http\Request
};

class App {
    public function __construct() {}

    public function run(): void
    {
        // Inițializează containerul
        $container = Container::getInstance();

        // Obține handler-ul de sesiune din container
        $sessionHandler = $container->make('session.handler');

        // Setează handler-ul de sesiune personalizat
        session_set_save_handler($sessionHandler, true);

        // Pornește sesiunea
        @session_start();

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        load_env();

        foreach ($container->getServicesByPriority() as $service) {
            $container->make($service);
        }

        // Obține HttpKernel din container și procesează cererea
        $kernel = $container->make(HttpKernel::class);
        $response = $kernel->handle($container->make(Request::class));

        // Trimite răspunsul către client
        $response->send();
    }

    protected function send($response): void
    {
        http_response_code($response->getStatusCode());
        echo $response->getBody();
    }
}