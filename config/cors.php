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

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'broadcasting/auth',
        'admin/api/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Add your frontend domains here
        'http://localhost:3000',       // React development
        'http://localhost:3001',       // Alternative React port
        'http://localhost:8080',       // Vue development
        'http://localhost:8081',       // Alternative Vue port
        'http://127.0.0.1:3000',       // Local IP React
        'http://127.0.0.1:8080',       // Local IP Vue
        'https://stg.connectinc.app',  // Production frontend
        'https://dick-connect-app-1zqh.vercel.app',   // Staging frontend
        '*',
        // For development, you can use '*' but it's not recommended for production
        // '*',
    ],

    'allowed_origins_patterns' => [
        // Allow any localhost with any port for development
        '/^http:\/\/localhost(:\d+)?$/',
        '/^http:\/\/127\.0\.0\.1(:\d+)?$/',
        '/^http:\/\/192\.168\.\d+\.\d+(:\d+)?$/', // Local network
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'max_age' => 0,

    'supports_credentials' => true,

];
