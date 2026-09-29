<?php

/*
|--------------------------------------------------------------------------
| SarionOS Module Authentication Boundary
|--------------------------------------------------------------------------
|
| Core is the sole SarionOS identity and SSO authority.
|
| Laravel requires a valid default guard while persisting database sessions.
| The module therefore keeps a framework-compatible session guard backed by
| a provider that never resolves or authenticates a local user.
|
*/

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => null,
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'sarionos_null_users',
        ],
    ],

    'providers' => [
        'sarionos_null_users' => [
            'driver' => 'sarionos_null',
        ],
    ],

    'passwords' => [],

    'password_timeout' => env(
        'AUTH_PASSWORD_TIMEOUT',
        10800
    ),
];
