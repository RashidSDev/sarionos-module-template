<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module identity
    |--------------------------------------------------------------------------
    */
    'module_key' => env('SARIONOS_MODULE_KEY', 'template'),
    'module_name' => env('SARIONOS_MODULE_NAME', 'Template'),

    /*
    |--------------------------------------------------------------------------
    | SarionOS Core URL
    |--------------------------------------------------------------------------
    */
    'core_url' => env('SARIONOS_CORE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Self URL
    |--------------------------------------------------------------------------
    */
    'self_url' => env('SARIONOS_SELF_URL', env('APP_URL')),

    'web_url' => env('SARIONOS_WEB_URL'),

    'cookie_domain' => env('SARIONOS_COOKIE_DOMAIN', env('SESSION_DOMAIN', '.sarionos.com')),

];