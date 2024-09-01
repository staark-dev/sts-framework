<?php
namespace STS\core\Facades;

class Sessions extends Facade {
    protected static function getFacadeAccessor() {
        return 'session.manager'; // Numele serviciului în container
    }
}