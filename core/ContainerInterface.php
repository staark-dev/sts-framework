<?php
declare(strict_types=1);
namespace STS\core;

interface ContainerInterface {
    public function bind($key, $resolver, $priority = 0);
    public function singleton($key, $resolver, $priority = 0);
    public function make($key);
    public function getServicesByPriority(): array;
}