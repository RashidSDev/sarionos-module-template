<?php

/*
|--------------------------------------------------------------------------
| SarionOS Module Authentication Boundary
|--------------------------------------------------------------------------
|
| Core is the sole SarionOS identity and SSO authority.
|
| Capability modules receive trusted Core identity/context data through the
| SarionOS integration contract. They do not own local Laravel users, guards,
| user providers or password-reset brokers.
|
| AppServiceProvider also clears Laravel 12's merged framework auth defaults
| during application registration.
|
*/

return [
    'defaults' => [
        'guard' => null,
        'passwords' => null,
    ],

    'guards' => [],

    'providers' => [],

    'passwords' => [],

    'password_timeout' => env(
        'AUTH_PASSWORD_TIMEOUT',
        10800
    ),
];
