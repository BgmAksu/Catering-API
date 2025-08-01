<?php

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'database' => getenv('DB_DATABASE') ?: 'dtt-catering',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],

    'api' => [
        'secret' => getenv('API_SECRET') ?: '97e01d39b8d5a12883bc3b776f21f6c4ac7732e4e9d96a0e87a3b5c0b15a79f4',
    ],
];
