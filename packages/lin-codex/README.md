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

## License

MIT. See [LICENSE](LICENSE).
