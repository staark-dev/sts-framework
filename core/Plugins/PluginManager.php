<?php
declare(strict_types=1);
namespace STS\core\Plugins;

class PluginManager {
    private array $plugins = [];

    public function loadPlugins($theme): void
    {
        $pluginDir = "themes/{$theme}/plugins/";
        foreach (glob($pluginDir . '*.php') as $plugin) {
            include $plugin;
            $this->plugins[] = basename($plugin, '.php');
        }
    }

    public function execute($hook, $params = []): void
    {
        foreach ($this->plugins as $plugin) {
            if (function_exists($plugin . "_$hook")) {
                call_user_func($plugin . "_$hook", $params);
            }
        }
    }
}
