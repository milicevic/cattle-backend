<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        // Development origins
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://localhost:8000',
        'http://localhost:8001',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8001',
        // Production frontend URL from environment variable
        env('FRONTEND_URL'),
        // Vercel preview deployments (wildcard pattern)
        env('FRONTEND_URL') ? preg_replace('/^https?:\/\//', 'https://*.vercel.app', env('FRONTEND_URL')) : null,
    ]),

    'allowed_origins_patterns' => [
        // Allow Vercel preview deployments (e.g., https://*.vercel.app)
        env('FRONTEND_URL') && strpos(env('FRONTEND_URL'), 'vercel.app') !== false 
            ? '/^https:\/\/.*\.vercel\.app$/'
            : null,
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
