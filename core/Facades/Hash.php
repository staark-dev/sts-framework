<?php
namespace STS\core\Facades;

class Hash extends Facade {
    protected static function getFacadeAccessor() {
        return 'hash'; // Numele serviciului în container
    }
}