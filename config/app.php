<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | Numele aplicației tale, folosit în diferite locuri din cod sau interfață.
    |
    */

    'name' => env('APP_NAME', 'STS Staark One'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | Mediul de lucru al aplicației: "local", "staging", "production", etc.
    | Această valoare poate afecta comportamentul anumitor servicii.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | Când aplicația este în modul debug, vor fi afișate toate erorile detaliate.
    | Acest lucru ar trebui activat doar în mediul de dezvoltare.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | URL-ul principal al aplicației, folosit în generarea de URL-uri complete.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Specifică fusul orar implicit pentru aplicația ta. Acest fus orar va fi
    | folosit de PHP și datele/orele generate de aplicație.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | Limba implicită a aplicației. Această valoare va fi folosită pentru
    | traduceri și alte funcționalități specifice localizării.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | Limba fallback care va fi utilizată atunci când traducerea curentă nu
    | este disponibilă pentru limba selectată.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | Cheia utilizată de aplicație pentru criptarea datelor. Această cheie
    | trebuie să fie un string aleatoriu de 32 de caractere.
    |
    */

    'key' => env('APP_KEY'),


    'theme' => [
        'active' => env('APP_THEME', 'default'),                // Numele temei active
        'path' => base_path('/\resources/\themes'),            // Calea către directorul de teme
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | Lista service providerilor care sunt încărcați automat la pornirea
    | aplicației. Poți adăuga aici provideri care gestionează servicii esențiale.
    |
    */

    'providers' => [
        // \App\Providers\AppServiceProvider::class,
        // \App\Providers\EventServiceProvider::class,
        \App\Providers\AppServiceProvider::class,
        \App\Providers\OrmServiceProvider::class,
        \App\Providers\SessionServiceProvider::class,
    ],

];
