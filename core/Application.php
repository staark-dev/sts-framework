<?php

namespace STS\Core;

class Application
{
    private $container; // Containerul de dependențe
    private $servicesToLoad; // Lista serviciilor de bază care trebuie încărcate

    public function __construct()
    {
        // Inițializează containerul de dependențe
        $this->container = new Container();

        // Execută verificările inițiale
        $this->runInitialChecks();

        // Încarcă configurația aplicației
        $this->loadConfiguration();

        // Înregistrează și configurează serviciile esențiale
        $this->registerCoreServices();

        // Încărcarea automată a serviciilor necesare
        $this->boot();
    }

    // Verificările inițiale
    private function runInitialChecks()
    {
        $this->checkPhpVersion();
        $this->checkPhpExtensions();
        $this->checkAppVersion();
        $this->checkDatabaseMigrations();
    }

    // Verificarea versiunii PHP
    private function checkPhpVersion()
    {
        $requiredPhpVersion = '7.4.0'; // Versiunea minimă necesară

        if (version_compare(PHP_VERSION, $requiredPhpVersion, '<')) {
            die("Aplicația necesită PHP versiunea $requiredPhpVersion sau mai recentă. Versiunea curentă: " . PHP_VERSION);
        }
    }

    // Verificarea modulului PHP necesare
    private function checkPhpExtensions()
    {
        $requiredExtensions = [
            'pdo',
            'mbstring',
            'curl',
            'openssl',
            'json',
            'gd',
            'xml',
        ];

        $missingExtensions = [];
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            }
        }

        if (!empty($missingExtensions)) {
            die("Următoarele module PHP necesare nu sunt instalate sau încărcate: " . implode(', ', $missingExtensions));
        }
    }

    // Verificarea versiunii aplicației
    private function checkAppVersion()
    {
        $currentVersion = '1.0.0'; // Versiunea curentă a aplicației
        $configVersion = $this->container->resolve('config')['app_version'] ?? null;

        if ($configVersion && version_compare($currentVersion, $configVersion, '<')) {
            die("Aplicația necesită o versiune mai recentă: $configVersion sau mai nouă. Versiunea curentă este $currentVersion.");
        }
    }

    // Verificarea tabelelor și migrațiilor bazei de date
    private function checkDatabaseMigrations()
    {
        $db = $this->container->resolve('database');

        // Verifică dacă tabelul pentru migrații există
        $query = $db->query("SHOW TABLES LIKE 'migrations'");
        if ($query->rowCount() == 0) {
            $this->runMigrations();
        } else {
            // Verifică dacă există migrații care nu au fost rulate
            $migratedVersions = $db->query("SELECT version FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
            $availableMigrations = glob(__DIR__ . '/../database/migrations/*.php');
            foreach ($availableMigrations as $migration) {
                $migrationVersion = basename($migration, '.php');
                if (!in_array($migrationVersion, $migratedVersions)) {
                    $this->runMigration($migration);
                }
            }
        }
    }

    // Execută toate migrațiile de bază de date
    private function runMigrations()
    {
        $migrations = glob(__DIR__ . '/../database/migrations/*.php');
        foreach ($migrations as $migration) {
            $this->runMigration($migration);
        }
    }

    // Execută o migrație specifică
    private function runMigration($migration)
    {
        $db = $this->container->resolve('database');
        require_once $migration;

        $migrationClass = basename($migration, '.php');
        $migrationObject = new $migrationClass();
        $migrationObject->up($db); // Rulează migrația

        $version = $migrationClass;
        $db->exec("INSERT INTO migrations (version) VALUES ('$version')");
    }

    // Funcție pentru a încărca configurația aplicației
    private function loadConfiguration()
    {
        $this->container->singleton('config', function ($container) {
            return include __DIR__ . '/../config/app.php'; // Încărcăm fișierul de configurare principal
        });
    }

    // Înregistrarea și configurarea serviciilor esențiale
    private function registerCoreServices()
    {
        $this->container->singleton('logger', function ($container) {
            $config = $container->resolve('config')['log'];
            return new \App\Services\Logger($config['file'], $config['level']);
        });

        $this->container->singleton('database', function ($container) {
            $config = $container->resolve('config')['database'];
            $logger = $container->resolve('logger'); // Accesează serviciul de logare
            $logger->info("Conectarea la baza de date: " . $config['dbname']);
            return new \App\Services\Database($config['host'], $config['dbname'], $config['username'], $config['password']);
        });

        // Înregistrează alte servicii esențiale...
    }

    // Încărcarea automată a serviciilor esențiale și logarea execuției
    private function boot()
    {
        $this->servicesToLoad = ['database', 'session', 'kernel', 'theme', 'logger', 'cache'];

        foreach ($this->servicesToLoad as $service) {
            try {
                $this->getService('logger')->info("Încărcarea serviciului: $service");
                $this->container->resolve($service);
                $this->getService('logger')->info("Serviciul $service a fost încărcat cu succes.");
            } catch (Exception $e) {
                $this->getService('logger')->error("Eroare la încărcarea serviciului $service: " . $e->getMessage());
                throw $e; // Oprește execuția aplicației în cazul unei erori critice
            }
        }
    }

    // Obținerea unui serviciu deja încărcat
    public function getService($name)
    {
        return $this->container->resolve($name);
    }
}
?>
