<?php

declare(strict_types=1);

return [
    /*
    | The first super admin, created by SuperAdminSeeder. Set these in .env.
    | Code reads config('identity...'), never env() directly, so it keeps working
    | when the configuration is cached in production.
    */
    'super_admin' => [
        'name' => env('ADMIN_NAME', 'Owner'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
