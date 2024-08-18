<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Aici specifici conexiunea implicită care va fi folosită de ORM
    | pentru a accesa baza de date.
    |
    */

    'default' => 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Aici poți defini toate conexiunile la baze de date ale aplicației tale.
    | Poți chiar configura conexiuni multiple și specifica opțiuni pentru
    | fiecare tip de bază de date (MySQL, PostgreSQL, SQLite, etc.).
    |
    */

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'sts_one'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],

        // Adaugă aici și alte conexiuni, cum ar fi PostgreSQL sau SQL Server
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Dacă folosești Redis pentru cache sau alte funcționalități,
    | poți specifica setările aici. Redis este o opțiune bună
    | pentru cache-ul ORM-ului.
    |
    */

    'redis' => [
        'client' => 'predis',  // Sau poți folosi 'phpredis'
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],
        'cache' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Setări specifice pentru cache-ul ORM-ului. Poți activa sau dezactiva
    | cache-ul pentru diferite operațiuni și specifica durata de viață
    | a cache-ului.
    |
    */

    'cache' => [
        'enabled' => true,
        'duration' => 3600, // Durata cache-ului în secunde
        'store' => 'redis', // Tipul de cache (file, redis, memcached, etc.)
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Defaults
    |--------------------------------------------------------------------------
    |
    | Aici poți specifica setări implicite pentru toate modelele ORM.
    | De exemplu, poți activa sau dezactiva automat timestamp-urile
    | sau poți specifica dacă modelele ar trebui să fie lazy-loaded
    | sau eager-loaded.
    |
    */

    'models' => [
        'timestamps' => true,   // Activează/dezactivează automat timestamp-urile
        'soft_deletes' => false, // Activează/dezactivează soft deletes
        'lazy_loading' => true, // Folosește lazy-loading pentru relații
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Logging & Profiling
    |--------------------------------------------------------------------------
    |
    | Poți activa logarea interogărilor SQL și profiling-ul pentru a analiza
    | performanța interogărilor executate de ORM.
    |
    */

    'logging' => [
        'enabled' => env('DB_LOGGING', false), // Activează/dezactivează logarea interogărilor
        'path' => storage_path('logs/sql.log'), // Locația fișierului de log
        'profiling' => env('DB_PROFILING', false), // Activează profiling-ul pentru interogări
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    |
    | Setări pentru gestionarea migrațiilor bazei de date. Aici poți specifica
    | locația fișierelor de migrare și alte setări.
    |
    */

    'migrations' => [
        'path' => database_path('migrations'),
        'table' => 'migrations',
    ],

];