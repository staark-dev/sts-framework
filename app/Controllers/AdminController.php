<?php

namespace STS\app\Controllers;

use STS\core\Http\Response;

class AdminController
{
    public function dashboard(): Response
    {
        return new Response('Welcome to the homepage');
    }

    public function users(): Response
    {
        return new Response('Welcome to the homepage');
    }
}