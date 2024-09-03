<?php

namespace STS\core\Http\Middleware;

use STS\core\Http\Request;
use STS\core\Http\Response;
use STS\core\Http\MiddlewareInterface;

class VerifyPostRequestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$params): Response
    {
        if ($request->getMethod() === 'POST') {
            // Verificarea CSRF, autentificare, etc.
            if (!$this->checkCsrfToken($request)) {
                return new Response('CSRF token mismatch', 403);
            }
        }

        // Continuă execuția următorului middleware sau acțiunii rutei
        return $next($request);
    }

    /**
     * Verifică tokenul CSRF.
     *
     * @param Request $request
     * @return bool
     */
    protected function checkCsrfToken(Request $request): bool
    {
        $csrfTokenFromRequest = $request->input('_token');
        $csrfTokenFromSession = $request->session()->get('_token');

        return $csrfTokenFromRequest === $csrfTokenFromSession;
    }

    public function parseParameters(string ...$params): array
    {
        return []; // Nu folosește parametri opționali
    }
}
