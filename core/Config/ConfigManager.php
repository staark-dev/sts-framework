<?php
namespace STS\core\Config;

class ConfigManager {
    protected $config = [];

    public function __construct($configPath) {
        $this->loadConfigurations($configPath);
    }

    protected function loadConfigurations($configPath) {
        foreach (glob($configPath . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }
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
