<?php
namespace App\Middleware;

use STS\core\Http\Request;
use STS\core\Http\Response;
use STS\core\Facades\ResponseFacade;
use STS\core\Facades\Auth;

class AdminAuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check() || !Auth::hasPermission('admin_access')) {
            new Response('Unauthorized', 401);
            ResponseFacade::redirect('/', 201);
        }

        return $next($request);
    }
}