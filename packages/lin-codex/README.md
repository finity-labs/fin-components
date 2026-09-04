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

A YAML block at the top of the file holds the article's metadata. The renderer strips it from the body; the file source reads it.

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

## License

MIT. See [LICENSE](LICENSE).
