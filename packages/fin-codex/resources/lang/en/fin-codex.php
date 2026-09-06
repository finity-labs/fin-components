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
        'validation' => [
            'slug_format' => 'The slug must be kebab-case segments separated by slashes.',
            'parent_missing' => 'No article exists at :parent.',
        ],
    ],
];
