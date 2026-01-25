<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ignored Name Parts
    |--------------------------------------------------------------------------
    |
    | Words listed here will be removed from the name before generating
    | initials. Case insensitive.
    |
    */
    'ignored_parts' => [
        'mr', 'mrs', 'ms', 'dr', 'prof', 'sr', 'jr',
        'phd', 'md', 'dds', 'esq', 'cpa',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Colors
    |--------------------------------------------------------------------------
    |
    | Fallback colors if the Filament theme color cannot be determined.
    | Leave default_bg null to use theme color as background.
    |
    */
    'default_bg' => null, //'#1f2937', // Gray 950
    'default_text' => '#ffffff',
];