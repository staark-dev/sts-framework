<?php

namespace STS\app\Middleware;

use STS\core\Http\Request;
use STS\core\Http\Response;
use STS\core\Http\MiddlewareInterface;

class CustomMiddlewareExample implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$params): Response
    {
        // Logica middleware-ului personalizat
        if (!$request->user()) {
            return new Response('Unauthorized', 401);
        }

        // Continuă execuția următorului middleware sau a acțiunii rutei
        return $next($request);
    }

    public function parseParameters(string ...$params): array
    {
        // Prelucrează parametrii opționali dacă este necesar
        return [];
    }
}
