<?php
namespace STS\core;

class ConfigManager {
    protected $config = [];
    
    public function __construct(array $config) {
        $this->config = $config;

        return $this;
    }

    public function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }
}
