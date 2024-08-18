<?php

namespace STS\core;

class PriorityQueue {
    private array $queue = [];

    public function add($service, $priority): void
    {
        $this->queue[] = ['service' => $service, 'priority' => $priority];
    }

    public function getSorted(): array
    {
        usort($this->queue, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        return array_column($this->queue, 'service');
    }
}
