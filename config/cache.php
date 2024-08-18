<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | Această opțiune controlează driverul implicit de cache care va fi folosit
    | de aplicație. Poți schimba acest driver la oricare dintre opțiunile
    | suportate: "file", "database", "redis", "memcached", etc.
    |
    */

    'default' => env('CACHE_DRIVER', 'file'),
    'cache_path' => storage_path('cache/data'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Aici poți defini toate "store"-urile de cache folosite în aplicație.
    | Poți chiar configura multiple store-uri pentru diferite tipuri de
    | cache, folosind același driver sau drivere diferite.
    |
    */

    'stores' => [

        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache/data'),
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Opțiuni specifice pentru Memcached
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        // Alte store-uri de cache...

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | Când folosim un store precum Memcached sau Redis, alte aplicații
    | pot folosi aceleași store-uri. Pentru a evita coliziunile de chei,
    | specificăm un prefix comun pentru toate cheile de cache ale aplicației.
    |
    */

    'prefix' => env('CACHE_PREFIX', 'sts_cache'),

];
