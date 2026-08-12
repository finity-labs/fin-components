# Installation

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- Filament v4 or v5

## Install

```bash
composer require finity-labs/fin-modal-table-select
```

The service provider registers itself through Laravel package discovery. There's nothing to add to your panel provider — the component works anywhere a Filament schema does.

## Tailwind setup (custom themes only)

If your panel uses a custom Filament theme, add the package views to the theme's CSS file so Tailwind compiles the classes they use:

```css
/* resources/css/filament/admin/theme.css */
@source '../../../../vendor/finity-labs/fin-modal-table-select/resources/**/*.blade.php';
```

Then rebuild:

```bash
npm run build
```

On Filament's default theme you can skip this — but if a display mode looks unstyled, this is the first thing to check.

## Translations

58 locales ship with the package (the same set as the other fin packages). To customize any of the strings:

```bash
php artisan vendor:publish --tag="fin-modal-table-select-translations"
```

See [the reference](reference.md#translations) for the key list.

## Verify it works

Drop the component into any form as a straight replacement for Filament's native one:

```php
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;

ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
```

With no extra configuration it behaves exactly like the stock `ModalTableSelect` (badge display). Everything else is opt-in — continue with [Getting started](getting-started.md).
