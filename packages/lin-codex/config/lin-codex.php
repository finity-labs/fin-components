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

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    |
    | "cache" controls where rendered articles are kept. A null store means
    | the default cache store. The cache key already contains the content
    | hash, the locale, the slug and a renderer fingerprint, so a TTL is only
    | a memory bound: null keeps entries forever, 0 disables caching, and an
    | integer is a lifetime in seconds.
    |
    | "limits" protect the Markdown parser against pathological input such
    | as thousands of nested blockquotes or emphasis markers on one line.
    | Input beyond a limit is flattened to text rather than rejected.
    |
    | "sanitizer" applies to HTML-format articles. The library default of
    | 20,000 bytes would silently truncate long articles; -1 lifts the limit.
    |
    */

    'render' => [
        'cache' => [
            'store' => null,
            'ttl' => null,
        ],
        'limits' => [
            'max_nesting_level' => 50,
            'max_delimiters_per_line' => 500,
            'max_autocompleted_cells' => 10000,
        ],
        'sanitizer' => [
            'max_input_length' => -1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The URL prefix article-to-article links resolve under: "[Roles](roles.md)"
    | becomes "/help/users/roles". The help center is mounted at the same
    | prefix. A full URL is accepted as well.
    |
    */

    'routes' => [
        'help_center' => '/help',
    ],

];
