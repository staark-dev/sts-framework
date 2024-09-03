<?php
namespace STS\core\Http\Middleware;

use STS\core\Http\Request;
use STS\core\Routing\Response;
use STS\core\Facades\Auth;

class CheckPermissionMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        // Logica pentru verificarea permisiunilor
        if (!Auth::hasPermission('access_route')) {
            // Aruncă eroare 403 - Forbidden
            return new Response('Forbidden Permission', 403);
        }

        // În cazul în care nu are permisiunea, returnează un răspuns 403 - Forbidden
        if (!Auth::hasPermission('guest_only')) {
            // Aruncă eroare 403 - Forbidden
            return new Response('Forbidden Permission', 403);
        }

        if (!Auth::hasPermission('admin_only')) {
            // Aruncă eroare 403 - Forbidden
            return new Response('Forbidden Permission', 403);
        }

        var_dump($request); // Arată o eroare pentru a testa middleware-ul
        return $next($request);
    }
}
