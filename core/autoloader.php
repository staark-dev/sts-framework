<?php

/**
 * Autoloader pentru încărcarea automată a claselor din aplicație
 */
function autoloader($className)
{
    // Definește maparea namespace-urilor la directoare
    $namespaceMap = [
        'STS\\App\\Controller' => __DIR__ . '/../app/Controller/',
        'STS\\App\\Services' => __DIR__ . '/../app/Services/',
        'STS\\App\\Providers' => __DIR__ . '/../app/Providers/',
        'STS\\App\\Helpers' => __DIR__ . '/../app/Helpers/',
        'STS\\App\\Middleware' => __DIR__ . '/../app/Middleware/',
        'STS\\Core' => __DIR__ . '/../core/',
        'STS\\Database' => __DIR__ . '/../database/',
        // Adaugă alte mapări de namespace-uri și directoare după necesități
    ];

    // Înlocuiește backslash-urile cu slash pentru compatibilitate cu sistemul de fișiere
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

    // Parcurge maparea namespace-urilor pentru a găsi fișierul corespunzător
    foreach ($namespaceMap as $namespace => $directory) {
        if (strpos($className, $namespace) === 0) {
            // Construiește calea completă către fișierul clasei
            $relativeClass = str_replace($namespace, '', $className);
            $filePath = $directory . ltrim($relativeClass, DIRECTORY_SEPARATOR) . '.php';

            if (file_exists($filePath)) {
                require_once $filePath;
                return;
            }
        }
    }

    // Dacă clasa nu este găsită, aruncă o excepție
    throw new Exception("Clasa $className nu a fost găsită în directoarele specificate.");
}

// Înregistrează funcția de autoload
spl_autoload_register('autoloader');
?>
