<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Súgó',
        'help' => 'Súgó',
    ],
    'button' => [
        'tooltip' => 'Súgó',
    ],
    'guest' => [
        'link' => 'Segítségre van szüksége?',
    ],
    'hint' => [
        'open' => 'Súgó megnyitása',
    ],
    'editor' => [
        'article' => 'Cikk',
        'articles' => 'Cikkek',
        'navigation' => 'Súgócikkek',
        'form' => [
            'identity' => 'Azonosító',
            'publishing' => 'Közzététel',
            'discovery' => 'Felfedezhetőség',
            'slug' => 'Slug (útvonal)',
            'slug_help' => 'Kötőjeles szegmensek perjellel elválasztva; a szülőnek léteznie kell.',
            'parent' => 'Szülő',
            'no_parent' => 'Legfelső szint',
            'icon' => 'Ikon',
            'order' => 'Sorrend',
            'format' => 'Formátum',
            'published' => 'Közzétéve',
            'visibility' => 'Láthatóság',
            'keywords' => 'Kulcsszavak',
            'related' => 'Kapcsolódó cikkek',
            'title' => 'Cím',
            'excerpt' => 'Kivonat',
            'body' => 'Törzs',
        ],
        'columns' => [
            'slug' => 'Cikk',
            'source' => 'Forrás',
            'published' => 'Közzétéve',
            'visibility' => 'Láthatóság',
            'format' => 'Formátum',
            'languages' => 'Nyelvek',
        ],
        'source' => [
            'database' => 'Adatbázis',
            'both' => 'Fájl és adatbázis',
            'file' => 'Fájl',
        ],
        'state' => [
            'present' => 'Lefordítva',
            'missing' => 'Hiányzik',
            'outdated' => 'Elavult',
        ],
        'filters' => [
            'published' => 'Közzétéve',
            'visibility' => 'Láthatóság',
            'format' => 'Formátum',
            'source' => 'Forrás',
            'missing' => 'Hiányzó nyelv',
            'outdated' => 'Elavult nyelv',
        ],
        'validation' => [
            'slug_format' => 'A slug kötőjeles szegmensekből álljon, perjellel elválasztva.',
            'parent_missing' => 'Nem létezik cikk a(z) :parent helyen.',
        ],
    ],
];
