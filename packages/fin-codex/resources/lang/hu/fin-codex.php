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
            'contexts' => 'Kontextusok',
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
        'tabs' => [
            'articles' => 'Cikkek',
            'files' => 'Fájlokból',
        ],
        'files' => [
            'title' => 'Cím',
            'locales' => 'Nyelvek',
            'path' => 'Fájl',
            'import' => 'Importálás és szerkesztés',
            'empty' => 'Minden fájlcikk már az adatbázisban van.',
        ],
        'imported' => [
            'title' => 'Cikk importálva',
            'body' => 'A(z) :path fájl mostantól figyelmen kívül marad; helyette az adatbázisban tárolt cikk jelenik meg.',
            'failed' => 'Az importálás sikertelen',
        ],
        'shadowed' => 'Ez a cikk elfedi a(z) :path fájlt, amely a cikk létezéséig figyelmen kívül marad.',
        'contexts' => [
            'panel' => 'Panel',
            'any_panel' => 'Bármely panel',
            'type' => 'Típus',
            'key' => 'Kulcs',
            'pattern' => 'URL-minta',
            'label' => 'Megjelenik itt',
            'add' => 'Kontextus hozzáadása',
            'declared' => 'Kódban deklarálva',
            'declared_by' => ':class deklarálta a(z) :panel panelhez',
        ],
        'copy' => [
            'label' => 'Másolás az alapértelmezett nyelvből',
            'heading' => 'Felülírja ezt a fordítást?',
            'description' => 'A cím, a kivonat és a törzs az alapértelmezett nyelv aktuális szövegére cserélődik.',
        ],
        'validation' => [
            'slug_format' => 'A slug kötőjeles szegmensekből álljon, perjellel elválasztva.',
            'parent_missing' => 'Nem létezik cikk a(z) :parent helyen.',
        ],
    ],
];
