<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'PaySoot',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'timezone' => 'UTC',
    
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key',
        'expiry' => (int) ($_ENV['JWT_EXPIRY'] ?? 3600),
        'algo' => 'HS256',
    ],
];