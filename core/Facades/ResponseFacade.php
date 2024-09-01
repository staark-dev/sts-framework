<?php
namespace STS\core\Facades;

class ResponseFacade extends Facade {
    protected static function getFacadeAccessor() {
        return 'http.response'; // Numele serviciului în container
    }
}
