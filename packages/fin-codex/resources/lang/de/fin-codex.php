<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Hilfe',
        'help' => 'Hilfe',
    ],
    'button' => [
        'tooltip' => 'Hilfe',
    ],
    'guest' => [
        'link' => 'Brauchen Sie Hilfe?',
    ],
    'hint' => [
        'open' => 'Hilfe öffnen',
    ],
    'editor' => [
        'article' => 'Artikel',
        'articles' => 'Artikel',
        'navigation' => 'Hilfeartikel',
        'form' => [
            'identity' => 'Identität',
            'publishing' => 'Veröffentlichung',
            'discovery' => 'Auffindbarkeit',
            'slug' => 'Slug (Pfad)',
            'slug_help' => 'Kebab-Case-Segmente, durch Schrägstriche getrennt; das übergeordnete Element muss existieren.',
            'parent' => 'Übergeordnet',
            'no_parent' => 'Oberste Ebene',
            'icon' => 'Symbol',
            'order' => 'Reihenfolge',
            'format' => 'Format',
            'published' => 'Veröffentlicht',
            'visibility' => 'Sichtbarkeit',
            'keywords' => 'Schlüsselwörter',
            'related' => 'Verwandte Artikel',
            'title' => 'Titel',
            'excerpt' => 'Auszug',
            'body' => 'Inhalt',
        ],
        'validation' => [
            'slug_format' => 'Der Slug muss aus Kebab-Case-Segmenten bestehen, durch Schrägstriche getrennt.',
            'parent_missing' => 'Unter :parent existiert kein Artikel.',
        ],
    ],
];
