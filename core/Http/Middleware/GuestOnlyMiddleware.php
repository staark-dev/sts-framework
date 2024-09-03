<?php

namespace STS\core\Http\Middleware;

use STS\core\Http\Request;
use STS\core\Http\Response;
use STS\core\Facades\Auth;
use STS\core\Facades\ResponseFacade;

class GuestOnlyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        // Verifică dacă utilizatorul este autentificat
        if (Auth::isLoggedIn()) {
            // Redirecționează utilizatorul autentificat către pagina principală sau altă pagină
            $response = new Response("You are already logged in. Please log out to access this page.", 304, ['Location' => route('home')]);
            $response->send();
        }

        // Continuă execuția pentru utilizatorii care nu sunt autentificați
        return $next($request);
    }
}
