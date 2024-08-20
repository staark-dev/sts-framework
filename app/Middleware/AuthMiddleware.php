<?php
namespace App\Middleware;

use STS\core\Http\Request;
use STS\core\Http\Response;
use STS\core\Auth\Auth;
use STS\core\Middleware\MiddlewareManager;

class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check() || !Auth::hasPermission('admin_access')) {
            return new Response('Unauthorized', 401);
        }

        return $next($request);
    }
}