<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Scrubbing
    |--------------------------------------------------------------------------
    |
    | Values matching these keys are redacted with [REDACTED] in error emails.
    | Each category targets a different data source. Keys are matched
    | case-insensitively.
    |
    */
    'scrub' => [
        'params' => [
            'password',
            'password_confirmation',
            'token',
            'secret',
            '_token',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
        ],

        'headers' => [
            'authorization',
            'cookie',
            'x-api-key',
        ],

        'env' => [
            'DB_PASSWORD',
            'APP_KEY',
            'MAIL_PASSWORD',
            'AWS_SECRET_ACCESS_KEY',
        ],

        'trace_args' => [
            'password',
            'secret',
            'token',
        ],
    ],
];
