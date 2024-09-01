<?php
namespace App\Middleware;

use STS\core\Http\Request;
use STS\core\Http\Response;
use STS\core\Middleware\MiddlewareManager;
use STS\core\Facades\ResponseFacade;
use STS\core\Facades\Auth;

class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            new Response('Unauthorized', 401);
            ResponseFacade::redirect('/', 201);
        }

        return $next($request);
    }
}