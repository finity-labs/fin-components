# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Package foundation: `codex_` prefixed tables for articles, translations, contexts, revisions and media, the `Article`, `ArticleTranslation`, `ArticleContext`, `ArticleRevision` and `Media` models with factories, int-backed enums with string keys, and the `codex` settings group (languages, default locale, fallback behaviour, revision retention).
- Markdown rendering through one locked-down league/commonmark environment: raw HTML stripped, unsafe links disabled, nesting and delimiter limits.
- GFM tables, task lists, strikethrough and autolinks.
- Heading ids derived from the heading text (`## Reset a password` gives `reset-a-password`, duplicates get `-2`, `-3`), a `#` permalink on every heading, and table-of-contents data for h2 and h3.
- GitHub-style callouts (`> [!NOTE]`, `[!TIP]`, `[!IMPORTANT]`, `[!WARNING]`, `[!CAUTION]`) with optional custom titles and translated default titles.
- `:::steps` containers around a numbered list, and `:::details Title` containers.
- Images as figures with lazy loading, a lightbox hook and an optional caption.
- Relative `.md` article links resolved under `routes.help_center` and marked with `data-codex-article`.
- External links open in a new tab with `rel="noopener noreferrer"`.
- HTML-format articles sanitized with an allowlist and given the same heading ids, anchors and link handling as Markdown.
- Plain-text extraction for search from either format.
- `ArticleRenderer` façade with a render cache keyed by content hash, format, locale, slug and a renderer fingerprint, so edits and config changes invalidate without a manual cache clear.
- Config keys `render.cache.store`, `render.cache.ttl`, `render.limits.*`, `render.sanitizer.max_input_length` and `routes.help_center`.
- Lang keys `callouts.*`, `anchor_label` and `details_default`.
