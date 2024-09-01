<?php
namespace STS\core\Facades;

class Database extends Facade {
    protected static function getFacadeAccessor() {
        return 'db.connection'; // Numele serviciului în container
    }
}