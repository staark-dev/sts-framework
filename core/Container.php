<?php
namespace STS\core;

use STS\core\ContainerInterface;
use \ReflectionClass;
use \ReflectionException;

class ServiceNotFoundException extends \Exception {
    public function __construct($serviceName) {
        parent::__construct("Service not found: $serviceName");
    }
}

final class Container implements ContainerInterface {
    protected static ?self $instance = null;
    private PriorityQueue $priorityQueue;
    private array $bindings = [];
    private array $singletons = [];
    private array $instances = [];
    private array $aliases = [];

    public function __construct() {
        $this->priorityQueue = new PriorityQueue();
    }

    public function bind($name, $resolver, &$priority = 0) {
        $this->bindings[$name] = $resolver;
        $this->priorityQueue->add($name, $priority);
    }

    public function singleton($name, $resolver, &$priority = 0) {
        $this->singletons[$name] = $resolver;
        $this->priorityQueue->add($name, $priority);
    }

    // Adăugarea metodei alias
    public function alias(string $alias, string $name): void
    {
        $this->aliases[$alias] = $name;
    }

    // Înregistrează o clasă în container cu o prioritate
    public function registerClass(string $name, int $priority = 0): void
    {
        $this->bindings[$name] = function ($container) use ($name) {
            return $container->resolve($name); // Utilizează metoda de rezolvare automată
        };

        // Adaugă clasa în PriorityQueue pentru a gestiona prioritățile
        $this->priorityQueue->add($name, $priority);
    }

    // Metoda pentru a verifica dacă un serviciu este înregistrat în container
    public function has(string $name): bool
    {
        // Verifică dacă serviciul este înregistrat ca singleton, binding sau alias
        return isset($this->bindings[$name]) || isset($this->singletons[$name]) || isset($this->aliases[$name]) || isset($this->instances[$name]);
    }

    public function make($name) {
        // Verifică dacă există un alias pentru serviciul solicitat
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }

        // Verifică dacă serviciul este deja o instanță singleton
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // Dacă serviciul este înregistrat ca singleton sau binding
        if (isset($this->bindings[$name])) {
            $resolver = $this->bindings[$name];
            $instance = $resolver($this);
        } elseif (isset($this->singletons[$name])) {
            $resolver = $this->singletons[$name];
            $instance = $resolver($this);
            $this->instances[$name] = $instance;
        } else {
            // Rezolvă automat dependențele utilizând reflecția
            $instance = $this->resolve($name);
        }

        return $instance;
    }

    private function resolve($name) {
        try {
            $reflection = new ReflectionClass($name);

            // Verifică dacă clasa poate fi instanțiată
            if (!$reflection->isInstantiable()) {
                throw new ServiceNotFoundException("Class [$name] is not instantiable.");
            }

            $constructor = $reflection->getConstructor();

            // Dacă clasa nu are constructor, creează o instanță simplă
            if (is_null($constructor)) {
                return new $name;
            }

            // Obține parametrii constructorului și rezolvă dependențele
            $parameters = $constructor->getParameters();
            $dependencies = $this->resolveDependencies($parameters);

            // Creează instanța clasei cu dependențele rezolvate
            return $reflection->newInstanceArgs($dependencies);

        } catch (ReflectionException $e) {
            throw new ServiceNotFoundException($name);
        }
    }

    private function resolveDependencies(array $parameters) {
        $dependencies = [];
    
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
    
            // Verifică dacă tipul este o instanță de ReflectionNamedType și dacă este o clasă
            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();
                $dependencies[] = $this->make($className);
            } else {
                // Dacă parametrul nu are un tip de clasă, gestionează parametrii nespecificați
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve the dependency [{$parameter->name}]");
                }
            }
        }
    
        return $dependencies;
    }    

    /*private function resolveDependencies(array $parameters) {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            if ($dependency === null) {
                // Dacă parametrul nu are un tip de clasă, gestionează parametrii nespecificați
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve the dependency [{$parameter->name}]");
                }
            } else {
                // Rezolvă automat dependența prin recursie
                $dependencies[] = $this->make($dependency->name);
            }
        }

        return $dependencies;
    }*/

    public function getServicesByPriority(): array {
        return $this->priorityQueue->getSorted();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}