<?php
namespace STS\app\Controllers;

use STS\core\Controller;

class HomeController extends Controller {
    public function index(): void
    {
        $this->view('home');
    }
}
