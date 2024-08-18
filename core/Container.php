<?php
namespace STS\core;
use ContainerInterface;

class ServiceNotFoundException extends \Exception {
    public function __construct($serviceName) {
        parent::__construct("Service not found: $serviceName");
    }
}

namespace STS\core;

final class Container implements ContainerInterface {
    protected static ?Container $instance = null;
    private array $bindings = [];
    private array $singletons = [];
    private array $instances = [];
    private array $servicePriority = [];
    private array $aliases = [];  // Adaugă aliasurile pentru servicii

    // Înregistrează un serviciu normal în container
    public function bind($name, $resolver, $priority = 0) {
        $this->bindings[$name] = $resolver;
        $this->servicePriority[$name] = $priority;
    }

    // Înregistrează un serviciu ca singleton
    public function singleton($name, $resolver, $priority = 0) {
        $this->singletons[$name] = $resolver;
        $this->servicePriority[$name] = $priority;
    }

    // Adaugă un alias pentru un serviciu existent
    public function alias($alias, $name) {
        $this->aliases[$alias] = $name;
    }

    // Metoda make care creează sau returnează o instanță a unui serviciu
    public function make($name) {
        // Verifică dacă numele are un alias
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }

        // Verifică dacă serviciul este deja instanțiat ca singleton
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // Construiește și returnează un singleton
        if (isset($this->singletons[$name])) {
            $this->instances[$name] = $this->build($this->singletons[$name]);
            return $this->instances[$name];
        }

        // Construiește și returnează un serviciu obișnuit
        if (isset($this->bindings[$name])) {
            return $this->build($this->bindings[$name]);
        }

        // Dacă clasa există, încearcă să o instanțiezi automat
        if (class_exists($name)) {
            return $this->build($name);
        }

        throw new ServiceNotFoundException("Service {$name} not found in container.");
    }

    // Verifică dacă un serviciu este înregistrat sub un anumit nume sau alias
    public function has($name): bool {
        return isset($this->bindings[$name]) || isset($this->singletons[$name]) || isset($this->instances[$name]) || isset($this->aliases[$name]);
    }

    // Metoda pentru construirea efectivă a unui serviciu
    private function build($resolver) {
        if (is_callable($resolver)) {
            return $resolver($this);
        }

        $reflector = new \ReflectionClass($resolver);

        if (!$reflector->isInstantiable()) {
            throw new ServiceNotFoundException("Class {$resolver} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $resolver;
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependencyType = $parameter->getType();

            if ($dependencyType && !$dependencyType->isBuiltin()) {
                $dependencies[] = $this->make($dependencyType->getName());
            } else {
                $dependencies[] = $parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : null;
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    public function getServicesByPriority(): array {
        arsort($this->servicePriority);
        return array_keys($this->servicePriority);
    }

    // Obține instanța singleton a containerului
    public static function getInstance(): Container {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}