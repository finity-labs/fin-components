# Codex for Filament

[![FILAMENT 4.x](https://img.shields.io/badge/FILAMENT-4.x-EBB304?style=flat-square)](https://filamentphp.com/docs/4.x/panels/installation)
[![FILAMENT 5.x](https://img.shields.io/badge/FILAMENT-5.x-EBB304?style=flat-square)](https://filamentphp.com/docs/5.x/panels/installation)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/finity-labs/fin-codex.svg?style=flat-square)](https://packagist.org/packages/finity-labs/fin-codex)
[![Tests](https://github.com/finity-labs/fin-codex/actions/workflows/tests.yml/badge.svg)](https://github.com/finity-labs/fin-codex/actions/workflows/tests.yml)
[![Code Style](https://github.com/finity-labs/fin-codex/actions/workflows/style.yml/badge.svg)](https://github.com/finity-labs/fin-codex/actions/workflows/style.yml)
[![License](https://img.shields.io/packagist/l/finity-labs/fin-codex.svg?style=flat-square)](https://packagist.org/packages/finity-labs/fin-codex)

In-app help for Filament panels. Codex puts a help drawer in the topbar, shows a badge on every page that has an article, and gives admins an editor to write and translate those articles without leaving the panel. Content, search and the JSON API come from [lin-codex](https://github.com/finity-labs/lin-codex), so the same articles also serve the parts of your app that live outside Filament.

## Features

- **Help drawer** on every panel page, opened from the topbar button, a keyboard shortcut, a `?codex=slug` link or a `codex:open` browser event.
- **Contextual help** — the drawer opens on the articles attached to the current resource or page. Attach them from the editor, or declare them in code with `HasHelp`.
- **Field hints** — a question-mark button next to a form field that opens the article at a specific heading.
- **Guest support.** The login, registration and password-reset pages get their own "Need help?" link and the same drawer.
- **Article editor** with Markdown, language tabs, image uploads, a preview, and an HTML → Markdown conversion for imported content.
- **File and database articles side by side.** Articles that live as Markdown files on disk show up in the editor and can be imported with one click.
- **Revision history** with a rendered preview and one-click restore, kept per language with a configurable ceiling.
- **Media manager** on the article, with a delete that refuses while a body still points at the file.
- **Settings page** for languages, the default language, the fallback behaviour and revision retention.
- **Coverage report** listing every screen in the panel and which of them have no article yet, with row actions that write, attach or import one.
- **Source warnings** — broken front matter, duplicate slugs and `HasHelp` declarations naming articles that do not exist, in one collapsed panel above the article list.
- **Optional global search integration**, off by default, appending a gated Help category to the panel's own search field.
- **Authorization** through a shipped `ArticlePolicy` you can replace, with Filament Shield support for the two pages and the resource.
- **English, German and Hungarian** out of the box.

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- Filament 4 or 5
- [`finity-labs/lin-codex`](https://github.com/finity-labs/lin-codex) ^0.2

Codex is split across two packages, and it matters for where you configure things. **lin-codex** owns the content: the `codex_*` tables, the Markdown renderer, the filesystem source, visibility rules, search, translations and the JSON API. It ships its own config file, its own install command and its own Blade drawer, and it works in any Laravel app with no Filament at all.

**fin-codex** — this package — is the panel layer on top: the drawer mount, the help button, the editor, the settings and coverage pages, and the authorization. It has no config file of its own. Anything about *content* (the media disk, search tuning, the help-center route, the article gate) is configured in `config/lin-codex.php`; anything that can differ between two panels is a fluent option on the plugin.

## Installation

```bash
composer require finity-labs/fin-codex
```

lin-codex comes along as a dependency. Install its schema and settings first:

```bash
php artisan codex:install
```

Then wire the panel:

```bash
php artisan fin-codex:install
```

The install command:

- Checks that lin-codex's articles table exists, and offers to run `codex:install` if it doesn't.
- Registers `FinCodexPlugin::make()` in one of your panel providers (it lists the panels it found; pass `--panel=admin` to skip the prompt).
- Offers to publish the translations and the views. Both default to **no** — a published copy stops receiving upstream changes.
- Registers the article resource in `config/filament-shield.php` if [Filament Shield](#filament-shield-integration) is installed, and runs `shield:generate`.

It never publishes or migrates anything belonging to lin-codex. That is `codex:install`'s job, and running it twice is safe.

Pass `--force` to overwrite already-published files, and `--no-interaction` to take every default (the first panel it finds, no publishing, Shield wiring on if the config is there).

### Register the plugin by hand

If you would rather not let a command rewrite a provider:

```php
use FinityLabs\FinCodex\FinCodexPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FinCodexPlugin::make(),
        ]);
}
```

Register it on as many panels as you like. Each panel gets its own options, its own drawer and its own copy of the pages.

### Styles

There is nothing to do. The drawer's stylesheet arrives through lin-codex's own hashed route, injected into `<head>` on every panel page. You do not need a custom Filament theme, and you do not need a `@source` line if you have one.

## Plugin options

Every option that can differ between two panels is a fluent method. All of them accept a closure as well as a literal, evaluated when the option is read.

```php
FinCodexPlugin::make()
    ->shortcut('ctrl+/')                       // keyboard shortcut, null or '' disables it
    ->drawerWidth(480)                         // drawer width in pixels
    ->helpButton()                             // show the topbar button (default: true)
    ->guestDrawer()                            // drawer and link on simple-layout pages (default: true)
    ->globalSearch()                           // Help category in the panel search (default: false)
    ->helpButtonRenderHook(PanelsRenderHook::TOPBAR_END)
    ->navigationGroup('Help')
    ->navigationSort(90)
    ->policyNamespace('App\\Policies')
    ->articleResource(MyArticleResource::class)
    ->settingsPage(MyHelpSettings::class)
    ->coveragePage(MyHelpCoverage::class)
```

| Method | Default | What it does |
|---|---|---|
| `shortcut(string\|Closure\|null)` | `'ctrl+/'` | The keyboard shortcut that opens the drawer. `null` or `''` turns it off for that panel. |
| `drawerWidth(int\|Closure)` | `480` | Drawer width in pixels. |
| `helpButton(bool\|Closure)` | `true` | Renders the topbar help button. `false` removes the button only — the drawer, its shortcut and field hints stay. |
| `guestDrawer(bool\|Closure)` | `true` | The "Need help?" link and the drawer on simple-layout pages: login, register, password reset, email verification and any host `SimplePage`. `false` removes all three there; signed-in pages are unaffected. |
| `globalSearch(bool\|Closure)` | `false` | Appends a Help category to the panel's global search results. See [Global search](#global-search). |
| `helpButtonRenderHook(string\|Closure)` | `TOPBAR_END` | Where the button renders. Set it explicitly and Codex honours it as given. Leave it alone and the button goes to the topbar, falling back to `SIDEBAR_FOOTER` on a panel with `->topbar(false)`. |
| `navigationGroup(string\|UnitEnum\|Closure\|null)` | `NavigationGroup::Help` | The navigation group for the resource and both pages. The default enum's label follows the panel locale. |
| `navigationSort(int\|Closure\|null)` | `null` | Sort for the article resource. Help settings files at `+1` and Help coverage at `+2`, so `->navigationSort(90)` gives 90, 91 and 92. Leave it null and Filament sorts the group by label. |
| `policyNamespace(string)` | `'App\Policies'` | Where Codex looks for your own `ArticlePolicy`. See [Authorization](#authorization). |
| `articleResource(class-string)` | built-in | Swap in a subclass of `FinityLabs\FinCodex\Resources\ArticleResource`. |
| `settingsPage(class-string)` | built-in | Swap in a subclass of `FinityLabs\FinCodex\Pages\HelpSettings`. |
| `coveragePage(class-string)` | built-in | Swap in a subclass of `FinityLabs\FinCodex\Pages\HelpCoverage`. |

> **The three class overrides must name a real subclass of ours.** Filament calls `registerRoutes()` and `registerNavigationItems()` statically on whatever string you pass at panel registration time, so a typo or a class that doesn't extend the built-in one is a fatal error on the next request, not a quietly ignored option. Keep the built-in slug (or override `getPages()` too) so the internal links keep resolving.

Extending is the intended way to adjust things. All three built-ins are non-final, and a subclass inherits the list, the filters, the "From files" tab, the form, the relation managers and every header action for free:

```php
use FinityLabs\FinCodex\Resources\ArticleResource;

class MyArticleResource extends ArticleResource
{
    public static function getNavigationBadge(): ?string
    {
        return null;   // skip the warnings count on this panel
    }
}
```

## Contextual help

An article shows up in the drawer on a given screen because it has a *context* pointing at that screen. Contexts come from two places: articles carry them in the database, added from the editor, and classes declare them in code.

### Declaring help in code

Implement `HasHelp` on a resource, a resource page or a custom page, and use the `WithHelp` trait to answer it from a property:

```php
use FinityLabs\FinCodex\Help\HasHelp;
use FinityLabs\FinCodex\Help\WithHelp;

class UserResource extends Resource implements HasHelp
{
    use WithHelp;

    protected static array $helpArticles = ['users', 'user-roles'];
}
```

Best article first. The property can also be a map, when one class needs different articles per panel:

```php
protected static array $helpArticles = [
    '*'     => ['users'],
    'staff' => ['staff-users', 'users'],
];
```

`'*'` is the entry for panels without a key of their own. A panel with neither gets nothing.

The class must still `implements HasHelp` — a trait cannot implement an interface, and the scanner looks for the interface. `WithHelp` only fills in `getHelpArticles(string $panelId): array` from the property; skip the trait and write the method yourself if you'd rather compute the list.

### What a declaration becomes

A declaration on a **resource** covers the whole resource: it folds in as a panel-scoped `class:` context on the resource, plus one `route:` context per registered page (list, create, edit, view). A declaration on a **resource page** like `EditUser` covers that page's route only. A declaration on a **custom page** covers that page class.

Declared slugs lead the drawer in the order you wrote them, count towards the topbar badge, count as covered on the coverage page, appear in lin-codex's JSON API, and go through the core's visibility gate like any stored article.

Three rules are worth knowing before you spread declarations around:

1. **A page-level declaration refines, it doesn't lead.** The core sorts `class:` contexts before `route:` ones regardless of order, so an `EditUser` declaration always follows the resource's list on the edit page. Declare on the resource whatever should come first.
2. **A declaration suppresses panel-less stored contexts on that page.** The core's panel-scoped pass wins when it is non-empty, so a stored context with no panel id stops appearing on a page whose class or route carries a declaration in that panel. Store panel-scoped contexts (or declare them) when both should show.
3. **A slug that doesn't exist is skipped, not shown.** It is reported once per class, panel and slug through lin-codex's source warnings, so the typo turns up on the [coverage page](#coverage-and-warnings) rather than in the drawer.

**Multi-configuration resources are not scanned.** The scanner walks `Panel::getResources()` and `Panel::getPages()`. Filament 5's `getResourceConfigurations()` — the same resource registered several times with different configurations — is not walked, so declarations on those registrations do nothing until a later release adds it.

## Field hints

`CodexHelp` puts a small question-mark button next to a form field. Clicking it opens the drawer on the article, scrolled to the heading if you named one.

The shortest form is the `codexHelp()` macro, available on every Filament field:

```php
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

TextInput::make('slug')->codexHelp('articles/slugs');

Select::make('role')->codexHelp('users', 'assigning-a-role');
```

The macro is sugar over `hintAction(CodexHelp::make(...))`. Since `CodexHelp::make()` returns a plain Filament `Action`, it drops anywhere an action is accepted:

```php
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use FinityLabs\FinCodex\Forms\CodexHelp;

// Next to a section heading
Section::make('Permissions')
    ->afterHeader([CodexHelp::make('users/permissions')]);

// On an infolist entry
TextEntry::make('status')
    ->hintAction(CodexHelp::make('orders', 'order-statuses'));

// In a table header
$table->headerActions([CodexHelp::make('orders')]);
```

Only fields get the macro; everything else takes `CodexHelp::make()`.

The hint is invisible when there is nothing to open. If the slug doesn't exist, or the core's gate says this viewer may not read that article, the action hides and the field renders as if no hint were set. The tooltip is the article's title in the reader's language.

The button is a real link. Its `href` is the help-center URL for the article, and the Alpine handler only cancels the navigation when a drawer is present on the page. On a page without one — or with JavaScript off — the click goes to the help center in the same tab.

**On an SPA panel**, Codex appends the help-center route pattern to Filament's SPA exceptions when the plugin boots. Without that, Livewire's navigate listener starts on `mousedown` and wins the race against the Alpine intercept, so the click would leave the panel even with a drawer open. A custom `lin-codex.routes.help_center` prefix is honoured, and chaining `->spaUrlExceptions([...])` after `->plugin()` keeps working — the plugin appends rather than replaces.

## Locale and theme

The drawer locks `app()->getLocale()` when it mounts and keeps it across later Livewire requests. The chrome around it — the button's tooltip and aria-label, the guest link, the drawer's tab labels — is rendered by `__()` on each request instead, so it follows the locale of whatever request drew it.

If you set the panel locale in middleware, register that middleware as persistent:

```php
$panel->middleware([
    SetLocale::class,
], isPersistent: true);
```

Without `isPersistent: true`, Livewire update requests skip the middleware and the chrome falls back to the app's default locale after the first interaction.

**One accepted rough edge:** on a panel with dark mode, a machine whose OS prefers dark while the stored panel theme is light can show the drawer and button in dark colours for the few dozen milliseconds before Alpine's theme binding adds the `light` class. Filament's own dark-mode loader has the mirror-image race. Panels without dark mode get a static `light` class and never flash.
