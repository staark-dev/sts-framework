<?php

namespace STS\core\Events;

class EventManager {
    private array $listeners = [];

    public function listen($event, $callback): void
    {
        $this->listeners[$event][] = $callback;
    }

    public function dispatch($event, $data = null): void
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $callback) {
                call_user_func($callback, $data);
            }
        }
    }
}
