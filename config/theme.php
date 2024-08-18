<?php
return [
    'active_theme' => env('APP_THEME', 'modern'),                // Numele temei active
    'theme_path' => base_path('/\resources/\themes'),            // Calea către directorul de teme
    'cache_path' => storage_path('framework/view')
];