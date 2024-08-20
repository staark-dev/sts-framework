<?php
$router = app('Router');

// Definirea rutelor
$router->get('/', 'HomeController@index')->name('home');

// Grupuri de rute cu middleware și prefix
$router->group(['prefix' => '/admin', 'middleware' => ['AdminAuthMiddleware']], function() use ($router) {
    $router->get('/dashboard', 'AdminController@dashboard')->name('admin.dashboard');
    $router->get('/users', 'AdminController@users')->name('admin.users');
});

$router->group(['prefix' => '/auth', 'middleware' => []], function() use ($router) {
    $router->get('/login', 'AuthController@login')->name('auth.login');
    $router->post('/login', 'AuthController@loginHandle')->name('auth.login.handle');
    $router->get('/signup', 'AuthController@create')->name('auth.signup');
    $router->post('/signup', 'AuthController@signupHandle')->name('auth.signup.handle');
    $router->get('/logout', 'AuthController@logout')->name('auth.logout');
    $router->get('{id}/profile', 'AuthController@profile')->name('auth.profile');
});

$router->get('/users', function() {
    echo "Welcome to users page !";
})->name('users');