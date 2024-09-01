<?php
namespace STS\core\Facades;

class Validator extends Facade {
    protected static function getFacadeAccessor() {
        return 'validator'; // Numele serviciului în container
    }
}