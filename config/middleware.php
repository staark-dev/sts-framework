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

    /*
    |--------------------------------------------------------------------------
    | Middleware-uri Personalizate
    |--------------------------------------------------------------------------
    |
    | Utilizatorii pot adăuga aici propriile middleware-uri personalizate.
    |
    */

    \STS\app\Middleware\CustomMiddlewareExample::class,
];
