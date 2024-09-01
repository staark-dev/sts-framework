<?php
namespace STS\core\Facades;

class Auth extends Facade {
    protected static function getFacadeAccessor() {
        return 'auth.service'; // Numele serviciului în container
    }
}