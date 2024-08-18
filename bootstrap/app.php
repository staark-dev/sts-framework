<?php
require_once ROOT_PATH . '/vendor/autoload.php';
require_once 'autoload.php';

use STS\core\Container;
// Creează și configurează instanța containerului
$container = Container::getInstance();


// Înregistrează configurațiile în container
$container->singleton('config', function() {
    return new \STS\core\Config\ConfigManager(__DIR__ . '/../config');
});

// Încărcarea providerilor definiți în `config/app.php`

foreach ($container->make('config')->get('app.providers') as $providerClass) {
    try {
        if (class_exists($providerClass) && method_exists($providerClass, 'register')) {
            $providerClass::register($container);
        }
    } catch (\Exception $e) {
        // Loghează eroarea sau aruncă o excepție
        error_log("Eroare la înregistrarea providerului: " . $providerClass);
    }
}


return $container;