<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Middleware-uri Globale
    |--------------------------------------------------------------------------
    |
    | Aceste middleware-uri vor fi aplicate tuturor cererilor procesate de aplicație.
    | Utilizatorii pot adăuga, elimina sau modifica middleware-urile globale fără
    | să afecteze nucleul aplicației.
    |
    */

    \STS\core\Http\Middleware\VerifyPostRequestMiddleware::class,
    \STS\core\Http\Middleware\AuthMiddleware::class,
    \STS\core\Http\Middleware\CheckPermissionMiddleware::class,
    // Adaugă alte middleware-uri globale aici...
];
