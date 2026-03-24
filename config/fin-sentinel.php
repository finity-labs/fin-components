<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Scrub Keys
    |--------------------------------------------------------------------------
    |
    | Request parameters matching these keys will be redacted in error emails.
    | Supports dot notation for nested keys.
    |
    */
    'scrub_keys' => [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'authorization',
        'cookie',
        'csrf',
        '_token',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
    ],
];
