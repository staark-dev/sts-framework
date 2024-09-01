<?php
namespace STS\core\Facades;

class Theme extends Facade {
    protected static function getFacadeAccessor() {
        return 'theme'; // Numele serviciului în container
    }
}