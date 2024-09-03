<?php
namespace STS\core\Http\Middleware;

use STS\core\Http\Request;
use STS\core\Http\Response;
use STS\core\Facades\Auth;

class AuthMiddleware
{
    /**
     * Metoda `handle` care verifică dacă utilizatorul este autentificat.
     *
     * @param \STS\core\Http\Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle(Request $request, callable $next)
    {
        // Verifică dacă utilizatorul este autentificat
        if (Auth::isLoggedIn()) {
            add_log("Middleware de autentificare in action. \033[33m%s\033[0m Utilizatorul este autentificat se redirectioneaza",  'Middleware');
            // Redirecționează utilizatorul către pagina principală sau altă pagină
            header("Location: /", true, 302);
            exit;
        }

        add_log("Middleware de autentificare in action. Utilizatorul este autentificat", 'Middleware');
        // Continuă execuția către următorul middleware
        return $next($request);
    }
}
