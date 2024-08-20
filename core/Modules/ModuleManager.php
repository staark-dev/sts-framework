<?php
declare(strict_types=1);
namespace STS\core\Modules;

class ModuleManager
{
    protected array $modules = [];
    protected string $modulePath;
    protected array $activeModules = [];

    public function __construct(string $modulePath)
    {
        $this->modulePath = $modulePath;
        $this->loadModules();
        $this->loadActiveModules();
    }

    protected function loadModules(): void
    {
        foreach (glob($this->modulePath . '/*', GLOB_ONLYDIR) as $moduleDir) {
            $moduleName = basename($moduleDir);
            $configFile = $moduleDir . '/module.json';
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                $this->modules[$moduleName] = $config;
            }
        }
    }

    protected function loadActiveModules(): void
    {
        foreach ($this->modules as $moduleName => $module) {
            if ($module['active']) {
                $this->registerModule($moduleName);
            }
        }
    }

    public function registerModule(string $moduleName): void
    {
        if (isset($this->modules[$moduleName])) {
            $module = $this->modules[$moduleName];
            $providerClass = $module['provider'];

            // Înregistrarea serviciului modulului în container
            if (class_exists($providerClass)) {
                $provider = new $providerClass();
                $provider->register();
            }
        }
    }

    public function activateModule(string $moduleName): void
    {
        if (isset($this->modules[$moduleName])) {
            $this->modules[$moduleName]['active'] = true;
            $this->registerModule($moduleName);
            $this->saveModuleConfig($moduleName);
        }
    }

    public function deactivateModule(string $moduleName): void
    {
        if (isset($this->modules[$moduleName])) {
            $this->modules[$moduleName]['active'] = false;
            $this->saveModuleConfig($moduleName);
        }
    }

    protected function saveModuleConfig(string $moduleName): void
    {
        $moduleConfig = $this->modules[$moduleName];
        $configPath = $this->modulePath . '/' . $moduleName . '/module.json';
        file_put_contents($configPath, json_encode($moduleConfig, JSON_PRETTY_PRINT));
    }

    public function getActiveModules(): array
    {
        return array_filter($this->modules, fn($module) => $module['active']);
    }

    public function getAllModules(): array
    {
        return $this->modules;
    }
}