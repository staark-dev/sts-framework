<?php
namespace STS\core\Http;

use STS\core\Http\Request;
use STS\core\Http\Response;

interface MiddlewareInterface
{
    /**
     * Gestionarea cererii prin middleware.
     *
     * @param Request $request
     * @param callable $next
     * @param string ...$params Argumente opționale (permisiuni, roluri, etc.)
     * @return Response
     */
    public function handle(Request $request, callable $next, string ...$params): Response;

    /**
     * Prelucrarea parametrilor opționali specifici middleware-ului.
     *
     * @param string ...$params
     * @return array
     */
    public function parseParameters(string ...$params): array;
}
