<?php
namespace STS\core\Http\Middleware;

use STS\core\Facades\Auth;

class PermissionMiddleware
{
    public function handle($request, callable $next, string $permission)
    {
        add_log('Checking permission: '. $permission);
        // Presupunem că există o metodă care obține utilizatorul curent
        $user = Auth::user();

        [$permissions, $permissionName] = explode(':', $permission, 2);
        
        // Verificăm dacă utilizatorul are permisiunea necesară
        if (!$user || !$user->hasPermission($permissionName)) {
            add_log("Permission check failed for: " . $permissionName);
            // Returnăm un răspuns de eroare (de exemplu, 403 Forbidden)
            $response = new \STS\core\Http\Response('Forbidden: 403', 403);
            $response->send();
            // Închidem scriptul către asemenea loc
            exit;
        }

        // Continuă execuția dacă utilizatorul are permisiunea
        add_log("Permission check passed for: " . $permission);
        return $next($request);
    }
}
