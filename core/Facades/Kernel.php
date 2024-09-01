<?php
namespace STS\core\Facades;

class Kernel extends Facade {
    protected static function getFacadeAccessor() {
        return 'http.kernel'; // Numele serviciului în container
    }
}
