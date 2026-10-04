<?php

declare(strict_types=1);

return [

    'admin' => [
        'email' => env('SEED_ADMIN_EMAIL', 'admin@copypastas.test'),
        'password' => env('SEED_ADMIN_PASSWORD', 'password'),
    ],

    'moderator' => [
        'email' => env('SEED_MODERATOR_EMAIL', 'moderador@copypastas.test'),
        'password' => env('SEED_MODERATOR_PASSWORD', 'password'),
    ],

];
