<?php
namespace STS\core\Facades;

use STS\core\Container;
abstract class Facade {
    protected static function getFacadeAccessor()
    {
        throw new \Exception('Facade does not implement getFacadeAccessor method.');
    }

    public static function __callStatic($method, $args)
    {
        $instance = Container::getInstance()->make(static::getFacadeAccessor());
        
        if (!$instance) {
            throw new \Exception('A service for this facade was not found in the container.');
        }

        return $instance->$method(...$args);
    }
}
