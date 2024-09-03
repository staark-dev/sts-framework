<?php
namespace STS\core\Http\Middleware;

use STS\core\Facades\Auth;
class AdminOnlyMiddleware
{
    public function handle($request, callable $next)
    {
        // Obține utilizatorul curent
        $user = Auth::user();

        // Verifică dacă utilizatorul este admin
        if (!$user || !$user->isAdmin()) {
            return new \STS\core\Http\Response('Forbidden', 403); // 403 Forbidden dacă nu este admin
        }

        // Continuă execuția middleware-ului următor
        return $next($request);
    }
}
