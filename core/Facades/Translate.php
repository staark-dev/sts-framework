<?php
namespace STS\core\Facades;

class Translate extends Facade {
    protected static function getFacadeAccessor() {
        return 'theme.trans'; // Numele serviciului în container
    }
}