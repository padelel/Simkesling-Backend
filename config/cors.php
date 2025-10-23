<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    // Allowed origins driven by env to support multiple dev/prod frontends
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://127.0.0.1:3000,http://localhost:3001,http://localhost:3002')),
    // Do not use ['*'] when supports_credentials = true
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    // Enable credentials so HttpOnly cookies are accepted
    'supports_credentials' => true,
];