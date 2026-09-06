<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Help',
        'help' => 'Help',
    ],
    'button' => [
        'tooltip' => 'Help',
    ],
    'guest' => [
        'link' => 'Need help?',
    ],
    'hint' => [
        'open' => 'Open help',
    ],
    'editor' => [
        'article' => 'Article',
        'articles' => 'Articles',
        'navigation' => 'Help articles',
        'form' => [
            'identity' => 'Identity',
            'publishing' => 'Publishing',
            'discovery' => 'Discovery',
            'slug' => 'Slug (path)',
            'slug_help' => 'Kebab-case segments separated by slashes; the parent must exist.',
            'parent' => 'Parent',
            'no_parent' => 'Top level',
            'icon' => 'Icon',
            'order' => 'Order',
            'format' => 'Format',
            'published' => 'Published',
            'visibility' => 'Visibility',
            'keywords' => 'Keywords',
            'related' => 'Related articles',
            'title' => 'Title',
            'excerpt' => 'Excerpt',
            'body' => 'Body',
        ],
        'columns' => [
            'slug' => 'Article',
            'source' => 'Source',
            'published' => 'Published',
            'visibility' => 'Visibility',
            'format' => 'Format',
            'languages' => 'Languages',
        ],
        'source' => [
            'database' => 'Database',
            'both' => 'File and database',
            'file' => 'File',
        ],
        'state' => [
            'present' => 'Translated',
            'missing' => 'Missing',
            'outdated' => 'Outdated',
        ],
        'filters' => [
            'published' => 'Published',
            'visibility' => 'Visibility',
            'format' => 'Format',
            'source' => 'Source',
            'missing' => 'Missing language',
            'outdated' => 'Outdated language',
        ],
        'validation' => [
            'slug_format' => 'The slug must be kebab-case segments separated by slashes.',
            'parent_missing' => 'No article exists at :parent.',
        ],
    ],
];
