<?php
return [
    'name' => env('APP_NAME', 'Veen HMS'),
    'brand' => 'Veen Africa',
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'timezone' => env('APP_TIMEZONE', 'Africa/Nairobi'),
    'session_name' => 'veen_session',
    'session_idle' => 7200,
    'max_login_attempts' => 5,
];
