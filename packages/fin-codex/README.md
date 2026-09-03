# Codex for Filament

In-app help for Filament panels. Codex adds a help drawer to your panel, a help button on every resource and page that has an article, and an editor where admins write and translate the articles themselves. Content and search come from [lin-codex](https://github.com/finity-labs/lin-codex), so the same articles also serve the parts of your app that live outside Filament.

> Early development. Nothing here is usable yet. Watch the [CHANGELOG](CHANGELOG.md) for the first release.

## What it will do

- **Help drawer** with search, opened from the topbar or a keyboard shortcut.
- **Contextual help** for resources and pages. The same article can serve one resource in several panels, or each panel can have its own.
- **Article editor** with a Markdown editor, image uploads, language tabs, and an optional revision history with automatic cleanup.
- **Field-level hints** that link a form field to an article section.
- **Guest support** on the login, registration, and password reset pages.
- **Coverage report** listing resources and pages that have no article yet.
- **Optional global search** integration, off by default.
- **AI drafting, rewriting, and translation** through [lin-ai](https://github.com/finity-labs/lin-ai), when installed.

## License

MIT. See [LICENSE](LICENSE).
