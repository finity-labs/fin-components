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

    /*
    |--------------------------------------------------------------------------
    | Users Table
    |--------------------------------------------------------------------------
    |
    | The table the author foreign keys point at: "created_by" and
    | "updated_by" on articles, "user_id" on revisions and "uploaded_by" on
    | media. The migrations read this value, so set it before migrating. The
    | foreign keys assume an unsigned big-integer primary key; user tables
    | keyed by UUID are not supported.
    |
    */

    'users_table' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | The filesystem disk uploaded images are stored on and the directory
    | inside that disk. Images are referenced from article bodies by their
    | plain disk URL (for example "/storage/codex/abc.png"), so the disk
    | needs a public URL for images to render.
    |
    */

    'media' => [
        'disk' => 'public',
        'directory' => 'codex',
    ],

];
