# Codex for Laravel

In-app help for Laravel applications. Codex serves help articles from Markdown files, the database, or both, and shows the right article for the page the user is on. It ships a Livewire help drawer, a JSON API for Inertia frontends, per-language content, and search.

> Early development. Nothing here is usable yet. Watch the [CHANGELOG](CHANGELOG.md) for the first release.

## What it will do

- **Contextual help.** Map articles to route names, page classes, or URL patterns. The drawer opens on the article that matches the current page.
- **Files, database, or both.** Ship default docs in your repo and let admins override or extend them in the database. Import and export commands move content either way.
- **Multilingual.** One article, one translation row per language, with a configurable fallback when a translation is missing.
- **Search.** Database full-text search per language out of the box. A Laravel Scout driver is optional.
- **Markdown with extras.** Callouts, step-by-step blocks with screenshots, tables of contents, and image lightboxes.
- **Works for guests.** Public articles show on login, registration, and password reset pages.
- **Frontend stubs.** Publishable React and Vue drawer components for Inertia apps, talking to the JSON API.

Filament panels get all of this plus an article editor and panel-aware contexts through [fin-codex](https://github.com/finity-labs/fin-codex).

## Writing articles

Articles are Markdown. Everything below degrades to plain CommonMark on GitHub or in any other viewer, so the files stay readable outside the app.

### Front matter

A YAML block at the top of the file holds the article's metadata. The renderer strips it from the body; the file source reads it. The full key list is under [Content sources](#content-sources).

```markdown
---
title: Roles
visibility: public
---

Body starts here.
```

### Callouts

GitHub-style alerts. The marker is case-insensitive. Text after the marker becomes the title; without it the title is the translated type name (`Note`, `Tip`, `Important`, `Warning`, `Caution`). Elsewhere they render as blockquotes.

```markdown
> [!WARNING] Before you delete a user
> Their articles stay published.
```

### Steps

Wrap a numbered list in a `:::steps` fence. The first paragraph of each item is the step title. Anything indented under it, such as a screenshot, a callout or a code block, belongs to that step. A screenshot line becomes a figure.

```markdown
:::steps
1. Open the users page

   ![Users page](/storage/codex/users.png "The users list")

2. Click **Add** and fill in the form
:::
```

### Details

A collapsible block with a summary line. Without a title the summary reads `Details`.

```markdown
:::details Advanced options
Only needed when the connection uses a proxy.
:::
```

### Images

An image on its own line becomes a figure. The title, if given, is the caption. Images load lazily and carry a lightbox hook.

```markdown
![Alt](/storage/codex/a.png "Caption")
```

### Links

Link to another article with its relative file path, optionally with a section. The path is resolved against the current article's slug, `.md` is dropped, and the href is built under `lin-codex.routes.help_center` (`/help` by default). Links to other hosts open in a new tab.

```markdown
See [Roles](roles.md) and [Invoices](../billing/invoices.md#totals).
```

### Headings

Every heading from `##` down gets an id from its text and a `#` permalink: `## Reset a password` gives `#reset-a-password`. Duplicate headings get `-2`, `-3` suffixes. Second- and third-level headings make up the table of contents.

### HTML articles

An article can also be stored as HTML. It is sanitized on every render with an allowlist: scripts, embeds, forms, event handlers and `style` attributes are dropped, and classes survive only when they start with `codex-`. Headings get the same ids and permalinks, and article links work the same way.

### Rendering from code

```php
use FinityLabs\LinCodex\Enums\ArticleFormat;
use FinityLabs\LinCodex\Rendering\ArticleRenderer;

$article = app(ArticleRenderer::class)->render($body, ArticleFormat::Markdown, 'en', 'users/roles');
$article->html;       // safe HTML with codex-* classes
$article->toc;        // [['level' => 2, 'text' => 'Reset a password', 'id' => 'reset-a-password'], ...]
$article->plainText;  // search text
```

Results are cached under a key built from the content hash, the format, the locale, the slug and a fingerprint of the renderer config, so an edit or a config change invalidates on its own; the store and TTL live under `lin-codex.render.cache`. Every class the renderer emits is prefixed `codex-`. The CSS for those classes ships with the help drawer (a later release).

## Content sources

Articles come from Markdown and HTML files, from the database, or from both. `lin-codex.source` picks which: `filesystem`, `database` or `composite` (the default). In composite mode a slug that exists in the database hides the file version for every language, so an admin can take over any article you ship. Composite assumes the `codex_*` tables exist; an install that only ships files sets `filesystem`.

### Folder layout

Files live under `resources/codex/{locale}/` by default. The locale folder is required even when you only write one language. `lin-codex.sources.filesystem.paths` is an ordered list of such folders; a later path replaces an earlier one per slug, whole article, so a package can ship its docs and the app can override single files.

```
resources/codex/
├── en/
│   ├── 01-intro.md            # slug "intro", order 1
│   ├── 02-users/
│   │   ├── index.md           # slug "users": the section's own article
│   │   ├── 01-roles.md        # slug "users/roles"
│   │   └── images/users.png
│   └── billing/
│       └── invoices.md        # slug "billing/invoices"; "billing" is a group with no article
└── de/
    ├── 01-intro.md
    └── 02-users/index.md
```

The slug is the path without the locale folder, the numeric prefixes and the extension. `01-intro.md` is `intro` with order 1. A prefix needs a separator, so `2fa.md` stays `2fa`. A folder with an `index.md` is a section: the index file is the section's own article and the other files are its children. A folder without one is a group, shown in the tree by its name. Both `.md` and `.html` files are articles; the extension sets the format.

### Front matter

Front matter is optional. Everything except `title` and `excerpt` is read from the default-language file only. Other languages contribute their title and excerpt; any other key in them is ignored with a warning.

```markdown
---
title: Users
excerpt: Who can sign in and what they may do.
icon: heroicon-o-users
order: 2
visibility: public
published: true
contexts:
  - route:users.index
  - url:/admin/users/*
  - class:App\Filament\Resources\UserResource
  - admin:class:App\Filament\Resources\UserResource
related:
  - users/roles
  - billing/invoices
keywords:
  - accounts
  - sign in
---
```

| Key | What it does |
|---|---|
| `title` | Falls back to the first level-one heading, which is then removed from the body, and then to the file name (`reset-password` becomes `Reset password`). |
| `excerpt` | A short summary for lists and search results. |
| `slug` | Replaces the last segment of the slug. The folder still decides the parent. |
| `icon` | An icon name for the tree and the drawer. |
| `order` | Sort position among siblings. Defaults to the numeric prefix, else 0. |
| `visibility` | `public` or `authenticated` (default). |
| `published` | Defaults to `true`. |
| `contexts` | Pages this article belongs to: `route:name`, `url:/pattern/*` or `class:Fully\Qualified\Page`, each with an optional `panel:` prefix. |
| `related` | Slugs of related articles. |
| `keywords` | Extra words folded into the search text. |
| `format` | `markdown` or `html`. Defaults to the extension. |

There's no `parent` key; the folder is the parent. Unknown keys are kept in the article's `meta` bag and survive export.

A few YAML rules worth knowing: quote a title with a colon in it (`title: "Users: overview"`), `published: no` reads as false, quote a date you want kept as text, and keys are case-sensitive. A file with invalid front matter is skipped and reported; the rest of the folder still loads. Everything reported ends up in `ContentSource::warnings()`.

### Languages

One file per language at the same relative path: `en/02-users/index.md` and `de/02-users/index.md` are the same article. An article that only exists in a non-default language still loads, with a warning, and takes its metadata from that file.

### Images

Reference images relative to the article file, the way any Markdown editor does: `images/users.png` or `../images/logo.png`. The file source rewrites them to `/codex/media/{locale}/{path}`, and a route serves them from the docs folders with cache headers. Images must sit inside a locale folder; a path that escapes it is left as written. Only png, jpg, jpeg, gif, webp and avif are served, since an SVG can carry scripts. An image is served only when no article references it or when one of the articles that do is visible to the current viewer, so screenshots inside internal articles stay internal. The prefix lives in `lin-codex.routes.media` and the middleware in `lin-codex.routes.middleware`.

### Freshness

Every read checks a cheap fingerprint per docs folder: the file count and the newest modification time. When it changes, the folder is rescanned and re-cached, so an edit shows on the next request without clearing anything. An edit that keeps the modification time isn't detected; `codex:cache-clear` (a later release) is the manual override.

### Reading from code

```php
use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\Enums\ContextType;

$source = app(ContentSource::class);
$source->findBySlug('users/roles');                          // ArticleData or null
$source->findByContext(ContextType::Route, 'users.index');   // ArticleData[] for that page
$source->tree();                                             // TreeNode[] roots
$source->allForSearch();                                     // SearchDocument[], one per language; the searcher builds its index from these
$source->warnings();                                         // SourceWarning[]
```

Every method returns plain readonly objects, never an Eloquent model. The source applies no locale, visibility or published filtering. That's what the read services under [Reading articles](#reading-articles) do.

## Reading articles

Three services answer the three questions the help UI asks: which articles belong to this page, show me this article, and show me the whole tree. Each takes a `Viewer` and an optional locale, and all three apply the same visibility and language rules, so the drawer, the API and a Tinker session always see the same thing.

### From Tinker

```php
use FinityLabs\LinCodex\Auth\ViewerResolver;
use FinityLabs\LinCodex\Contexts\ContextResolver;
use FinityLabs\LinCodex\Contexts\PageContext;
use FinityLabs\LinCodex\Reading\ArticleReader;
use FinityLabs\LinCodex\Reading\TreeBuilder;

$viewer = app(ViewerResolver::class)->resolve();          // guest in Tinker unless you Auth::login() first

app(ContextResolver::class)->resolve(new PageContext('users.index', '/users'), $viewer);   // ArticleData[] for that page, best first
app(ArticleReader::class)->read('users/roles', $viewer, 'de');                            // ReadArticle or null
app(TreeBuilder::class)->build($viewer);                                                   // TreeNode[] with translated labels
```

`ReadArticle` carries the `article`, the chosen `translation` and its `locale`, `isFallback`, the `rendered` result (`html` and `toc`), `related` entries (`slug` and `title`) the same viewer may read in the same language, and `breadcrumbs` for the visible ancestors. A translation from the database carries `updatedAt`, the ISO 8601 time of its last change; file articles report `null`. In a request, `RequestContextDetector::detect($request, $pageClass, $panelId)` builds the `PageContext` from the route name and path; hosts that know more, like a Filament panel with its resource class and panel id, pass them in. `PageContext::toArray()` and `fromArray()` let a Livewire component keep it in state, captured once at mount.

### Who sees what

An article is visible when it's published, when it's `public` or the viewer is signed in, and when every parent article on its slug path passes the same test. A section marked `authenticated` therefore hides everything below it, public children included; move a child out of the section if it should stay public. Folders without an index file are groups, not articles, and hide nothing. Guests only ever get public articles.

Signed in means the guard in `lin-codex.auth.guard` has a user. `null` (the default) is the app's default guard, and it's one guard, not a list. `lin-codex.auth.gate` can name an invokable class `(Viewer $viewer, ArticleData $article): bool` that runs after the other checks and can only hide articles, never reveal them.

Hidden, restricted and missing articles are all the same answer: `null` from the reader, absent from the tree, an empty list from the context resolver, 404 from the media route, never 403. Nothing confirms that an article exists.

### Language fallback

The `codex` settings decide the languages: `languages` is the list a translation may be requested in, `default_locale` the one every article must have, and `fallback` what happens when a translation is missing. Matching is exact against that list, so `de_DE` doesn't fall back to `de`, and a locale that isn't listed counts as a missing translation. With `ShowDefault` you get the default-language translation with `isFallback` set, and the UI shows `__('lin-codex::lin-codex.fallback_notice', ['language' => ...])`, or simply `LocaleResolver::fallbackNotice($read->locale)`. With `Hide` the article is missing in that language, and children of a section hidden this way move up to the nearest visible ancestor.

The tree and the context lookup apply the same rule, so a page never offers an article the reader would then refuse. Folder group labels come from `lin-codex::lin-codex.groups.<full slug>` (`groups.billing/archive`) and default to the humanised folder name.

### Page contexts

A context key says which pages an article belongs to. There are three kinds:

| Key | Matches |
|---|---|
| `class:App\Filament\Resources\UserResource` | the exact class name the host reports; no parent classes or interfaces |
| `route:users.*` | the exact route name, or every name under a trailing `*` |
| `url:/users/*/edit` | the request path without the query string; `*` is one segment, `**` any depth |

The catch-alls `route:*` and `url:/**` are allowed and sort last. Prefix a key with a panel id (`admin:route:users.index`) to scope it to that panel.

Resolution takes the articles for the current panel first and falls back to panel-less ones only when the panel gave nothing visible. Within that, exact keys come before wildcards, then class before route before url, then the order the author gave, then the slug. One article may list many contexts, and many articles may share one; the drawer opens the first.

## Searching

Users find articles by typing words in their own language. Results follow the same visibility and language rules as everything else, so a search never lists an article the reader would then refuse. The same query returns the same hits in the same order on MySQL, MariaDB, PostgreSQL, SQLite and on a file-only install, because the database only pre-filters rows; PHP decides what matches and how it ranks.

### From code

```php
use FinityLabs\LinCodex\Auth\ViewerResolver;
use FinityLabs\LinCodex\Search\Searcher;

$viewer = app(ViewerResolver::class)->resolve();
$result = app(Searcher::class)->search('passwort zurück', $viewer, 'de', 10);   // SearchResult

$result->hits;                // SearchHit[]: slug, title, sectionPath, snippet, matchedField, score, isFallback
$result->total;               // number of hits returned (no pagination)
$result->rateLimited;         // true when the viewer is over the limit; hits is empty then
$result->retryAfterSeconds;   // seconds until the next allowed search, or null
```

The locale defaults to the app locale. `limit` defaults to `search.limit` (10) and is capped at `search.max_limit` (50). `snippet` is HTML: the matched word prefixes are wrapped in `<mark>` and everything else is escaped, so it's safe to print with `{!! !!}`. `sectionPath` holds the ancestor article titles, root first, for a "Users › Roles" line. `isFallback` and the fallback notice work as they do for `ReadArticle`. `matchedField->key()` is `title`, `keywords`, `excerpt` or `body`.

### What gets indexed

Every translation row has a `search_text` column holding the title, the article's keywords, the excerpt and the plain text of the body, lowercased and accent-folded. That's why `uber` finds `über`, `strasse` finds `Straße` and `hoseg` finds `hőség`. The column is filled when a translation is saved and refreshed when the article's keywords or format change. Rows written with the query builder skip the model hooks and stay unindexed. File articles get the same text when their folder is scanned.

Folding transliterates to ASCII, so scripts without a Latin transliteration (Chinese, Japanese, Korean) fold to nothing and aren't searchable in this release.

### How matching works

A query needs at least two characters (`search.min_length`); anything shorter returns an empty result without touching the rate limit. The query is folded the same way as the text and split into words. Every word must match (AND), and a word matches the start of a word in the text, so `pass` finds `password` but `word` doesn't.

Ranking happens in PHP. A hit in the title beats one in the keywords, which beats the excerpt, which beats the body. Within a tier the exact phrase and repeated words earn a bonus, and ties break by title, then slug.

### Engines

`search.engine` picks the database pre-filter. The default, `like`, runs a word-start `LIKE` per token and works the same on every database. `fulltext` uses the index: MySQL and MariaDB match in boolean mode, PostgreSQL uses `to_tsquery` with the text search configuration in `search.pgsql_language` (the index is built with the same configuration, so keep `simple` for a manual written in several languages), and SQLite stays on `LIKE`. The migration creates the index whichever engine is set, so switching is a config change, `CODEX_SEARCH_ENGINE=fulltext` in `.env`, not a migration.

Words shorter than three characters and MySQL stopwords (`the`, `und`, ...) would be dropped by the full-text engines, so with `fulltext` a query containing one takes the `LIKE` path, and a full-text query that finds nothing is retried once with `LIKE`. Either way the hits, their order and the snippets are the same, because the matcher re-checks every candidate in PHP. `search.candidates` (200) caps the rows the pre-filter hands to PHP.

### File-only installs

The filesystem source is searched through an in-memory index: the folded documents are cached under one key and rebuilt when the content changes, so an edit shows on the next search. A composite install searches the database and the file-only articles and merges them; a slug that exists in the database wins. `codex:cache-clear` (a later release) drops the index.

### Rate limits

`search.rate_limit.guest` (30 per minute, by IP) and `search.rate_limit.user` (120 per minute, by user id) throttle searches; `null` disables a tier. The limit lives in the search service, so the JSON API and the drawer share one counter. Over the limit the result is empty with `rateLimited` set and `retryAfterSeconds` saying how long to wait; nothing is thrown. Queries under the minimum length don't count. Behind a proxy, configure trusted proxies so the IP is the client's and not the proxy's.

## JSON API

Four GET endpoints under `routes.api` (`/codex/api` by default) answer the same questions the services do, as JSON. They run on the `routes.middleware` group (`web` by default), so the session identifies the viewer and an Inertia page needs no token. Every read applies the visibility and language rules described above: a guest gets public articles only, and a hidden article is a 404. The contract is frozen; later releases add keys, they don't rename or remove them.

| Endpoint | Answers |
|---|---|
| `GET /codex/api/tree?locale=` | the tree the viewer sees |
| `GET /codex/api/articles/{slug}?locale=` | one rendered article; the slug may contain slashes (`articles/users/roles`) |
| `GET /codex/api/search?q=&limit=&locale=` | the hits with snippets |
| `GET /codex/api/context?route=&path=&class=&panel=&locale=` | the ordered articles for a page, built from the query string, never from the request the API itself received |

### Envelope

Every success is `{ "data": ..., "meta": ... }`. `meta.locale` is the requested locale, or the app locale, and `meta.defaultLocale` the default from the settings; both are on every answer. The article adds `isFallback`; search adds `query`, `total`, `limit`, `rateLimited` and `retryAfterSeconds`; context echoes `route`, `path`, `class` and `panel` as the server understood them.

```json
{
  "data": {
    "slug": "intro",
    "title": "Introduction",
    "excerpt": "What Codex does and where to start.",
    "locale": "en",
    "isFallback": false,
    "format": "markdown",
    "html": "<p>Welcome to the help center. ...</p>",
    "toc": [{ "level": 2, "text": "Where to start", "id": "where-to-start" }],
    "breadcrumbs": [],
    "related": [{ "slug": "users", "title": "Users" }, { "slug": "users/roles", "title": "Roles" }],
    "icon": "heroicon-o-book-open",
    "updatedAt": null
  },
  "meta": { "locale": "en", "defaultLocale": "en", "isFallback": false }
}
```

Tree nodes carry `slug`, `title`, `icon`, `isGroup`, `isFallback`, `hasArticle` and `children`. Search hits carry `slug`, `title`, `sectionPath`, `snippet`, `matchedField`, `score` and `isFallback`. Context entries carry `slug`, `title`, `excerpt` and `isFallback`. `format` and `matchedField` are the enum keys: `markdown` or `html`, and `title`, `keywords`, `excerpt` or `body`.

### Errors

Errors are `{ "message": "..." }`: 404 for a missing, hidden or unpublished article (never 403), 422 for a missing `q` or a `limit` that isn't a whole number, and 429 with a `Retry-After` header when the search limiter refuses. A query under `search.min_length` is a 200 with empty data. A `locale` outside the settings list isn't an error either: it counts as a missing translation, so the article comes back with `isFallback` set or as a 404, by the `fallback` setting.

### Config

`routes.api` sets the prefix and `routes.middleware` the group. Swapping the group, for example to `api` with Sanctum, is the whole story for token auth; the endpoints don't care how the viewer was identified.

## React and Vue stubs

Inertia apps get a help drawer as published components:

```bash
php artisan vendor:publish --tag=lin-codex-react
php artisan vendor:publish --tag=lin-codex-vue
```

The tags are alternatives and both land in `resources/js/codex`: `types.ts` (the payloads as TypeScript types), `codex.ts` (a fetch client over the four endpoints), `HelpButton` and `HelpDrawer` (`.tsx` or `.vue`), and a README. The drawer opens on the button, on `Ctrl+/`, on a `codex:open` window event with an optional slug and on a `?codex=slug` query parameter; it shows the current page's articles first, then search and the tree, and renders an article with the fallback notice when it came from another language.

The drawer learns which page it is on from Inertia shared props. Share the prefix and the page context from `HandleInertiaRequests`:

```php
use FinityLabs\LinCodex\Contexts\RequestContextDetector;

public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'codex' => [
            'prefix' => config('lin-codex.routes.api'),
            'context' => app(RequestContextDetector::class)->detect($request)->toArray(),
        ],
    ];
}
```

Then `<HelpDrawer prefix={codex.prefix} context={codex.context} />` in the React layout, or `<HelpDrawer :prefix="page.props.codex.prefix" :context="page.props.codex.context" />` in the Vue one. After publishing the files are the app's: nothing is loaded automatically, there's no npm package, and every class is prefixed `codex-` so the package stylesheet of a later release applies if you include it. The published README has the props, the client and the event.

## License

MIT. See [LICENSE](LICENSE).
