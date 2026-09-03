<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Every table the package creates is prefixed with "codex_" so it never
    | collides with tables in the host application. Change the names here
    | before running the migrations if you need something else.
    |
    */

    'table_names' => [
        'articles' => 'codex_articles',
        'article_translations' => 'codex_article_translations',
        'article_contexts' => 'codex_article_contexts',
        'article_revisions' => 'codex_article_revisions',
        'media' => 'codex_media',
    ],

];
