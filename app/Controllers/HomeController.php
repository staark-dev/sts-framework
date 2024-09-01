<?php
namespace STS\app\Controllers;

use STS\core\Controller;

class HomeController extends Controller {
    public function index(): void
    {
        // Load the home view with the title 'Home'
        $this->view('home');
    }
}
