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
- **Frontend stubs.** Publishable React and Vue components for the Inertia starter kits.

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

Results are cached under a key built from the content hash, the format, the locale, the slug and a fingerprint of the renderer config, so an edit or a config change invalidates on its own; the store and TTL live under `lin-codex.render.cache`. Every class the renderer emits is prefixed `codex-`. The CSS for those classes ships in a later release.

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

Reference images relative to the article file, the way any Markdown editor does: `images/users.png` or `../images/logo.png`. The file source rewrites them to `/codex/media/{locale}/{path}`, and a route serves them from the docs folders with cache headers. Images must sit inside a locale folder; a path that escapes it is left as written. Only png, jpg, jpeg, gif, webp and avif are served, since an SVG can carry scripts. The prefix lives in `lin-codex.routes.media` and the middleware in `lin-codex.routes.middleware`.

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
$source->allForSearch();                                     // SearchDocument[], one per language
$source->warnings();                                         // SourceWarning[]
```

Every method returns plain readonly objects, never an Eloquent model. The source applies no locale, visibility or published filtering; that's the read services' job in a later release.

## License

MIT. See [LICENSE](LICENSE).
