<?php
namespace STS\app\Controllers;

use STS\core\Controller;
use STS\core\Facades\Auth;
use STS\core\Http\Response;
use STS\core\Http\Request;
use STS\core\Facades\Database;

class HomeController extends Controller {
    protected array $middleware = [];

    protected array $middlewareForActions = [
        //'index' => ['check_permission:permission:access_route'],
        'dashboard' => ['check_permission:permission:access_route', 'check_permission:permission:access_admin_panel'],
    ];    

    public function index()
    {
        $this->view('home', '', []);
    }

    public function dashboard(Request $request): Response
    {
        // Returnează un obiect de tip Response
        return new Response('Dashboard loaded successfully', 200);
    }
}
