<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // PERBAIKAN: Parse FRONTEND_URLS dari .env dengan benar
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('FRONTEND_URLS', '')))),

    'allowed_origins_patterns' => ['*'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // PENTING: Karena withCredentials: true di frontend
    'supports_credentials' => true,

];