<?php

declare(strict_types=1);

return [

    'enums' => [
        'article_format' => [
            'markdown' => 'Markdown',
            'html' => 'HTML',
        ],
        'visibility' => [
            'public' => 'Public',
            'authenticated' => 'Authenticated',
        ],
        'context_type' => [
            'class' => 'Page class',
            'route' => 'Route',
            'url' => 'URL',
        ],
        'revision_reason' => [
            'manual' => 'Manual',
            'import' => 'Import',
            'ai_rewrite' => 'AI rewrite',
        ],
        'fallback_behaviour' => [
            'show_default' => 'Show default language',
            'hide' => 'Hide',
        ],
    ],

    'callouts' => [
        'note' => 'Note',
        'tip' => 'Tip',
        'important' => 'Important',
        'warning' => 'Warning',
        'caution' => 'Caution',
    ],

    'anchor_label' => 'Link to :heading',
    'details_default' => 'Details',

];
