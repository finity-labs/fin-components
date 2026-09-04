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
- `ContentSource` contract (`all`, `findBySlug`, `tree`, `findByContext`, `allForSearch`, `warnings`) returning readonly `ArticleData`, `TranslationData`, `ContextData`, `TreeNode`, `SearchDocument` and `SourceWarning` objects, never Eloquent models.
- Filesystem source: Markdown and HTML articles under `{path}/{locale}/`, numeric prefixes for ordering, `index.md` sections, groups for folders without one, YAML front matter (`title`, `excerpt`, `slug`, `icon`, `order`, `visibility`, `published`, `contexts`, `related`, `keywords`, `format`, unknown keys kept in `meta`), title from the first heading, default-language precedence for shared keys, relative image rewriting, search text at scan time, a fingerprint-checked cache that needs no manual clear, and collected warnings.
- Database source over the `codex_*` tables, and a composite source where a database slug hides the file version for every language.
- Config keys `source`, `sources.filesystem.paths`, `routes.media` and `routes.middleware`.
- Media route `/codex/media/{locale}/{path}` streaming images from the docs folders with cache headers, an image-only allowlist and traversal protection.
- Article links written with numeric file prefixes (`01-roles.md`) or pointing at `index.md` resolve to the right slug, and links inside section files resolve against their folder.
- Lang keys `source_warnings.*` and `enums.source_warning_kind.*`.
