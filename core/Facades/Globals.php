<?php
namespace STS\core\Facades;

class Globals extends Facade {
    protected static function getFacadeAccessor() {
        return 'global.vars'; // Numele serviciului în container
    }
}