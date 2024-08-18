<?php
use STS\core\Routing\Router;

Router::get('/', 'HomeController@index')->name('home');

// Grupuri de rute cu middleware și prefix
Router::group(['prefix' => '/admin', 'middleware' => ['AdminAuthMiddleware']], function() {
    Router::get('/dashboard', 'AdminController@dashboard')->name('admin.dashboard');
    Router::get('/users', 'AdminController@users')->name('admin.users');
});

Router::get('/user/{id}', function ($id) {
    //return new \STS\core\Http\Response("Welcome to user profile {$id} !", 200);
    $theme = new \STS\core\Themes\ThemeManager(); //Container::getInstance()->make(ThemeManager::class);
    $theme->assign('title', 'Welcome to user');

    // Utilizează metoda view definită în HelperTrait
    $theme->display('user');
});

Router::group(['prefix' => '/auth'], function() {
    Router::get('/login', 'AdminController@dashboard')->name('auth.login');
    Router::get('/signup', 'AdminController@users')->name('auth.signup');
    Router::get('/logout', 'AdminController@users')->name('auth.logout');
    Router::get('/profile', 'AdminController@users')->name('auth.profile');
});