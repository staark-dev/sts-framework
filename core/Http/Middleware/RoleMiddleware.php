<?php
namespace STS\core\Http\Middleware;
use STS\core\Facades\Auth;
use STS\core\Http\MiddlewareInterface;

class RoleMiddleware implements MiddlewareInterface
{
    public function handle($request, callable $next, string $role)
    {
        // Obține utilizatorul curent
        $user = Auth::user();

        // Verifică dacă utilizatorul are rolul specificat
        if (!$user || !$user->hasRole($role)) {
            return new \STS\core\Http\Response('Forbidden', 403); // 403 Forbidden dacă nu are rolul
        }

        // Continuă execuția middleware-ului următor
        return $next($request);
    }
}
