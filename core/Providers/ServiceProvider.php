<?php 
declare(strict_types=1);
namespace STS\core\Providers;

use STS\core\Container;

abstract class ServiceProvider {
    protected ?Container $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    abstract public function register(): void;
    abstract public function boot(): void;
}
