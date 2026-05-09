# FinModalTableSelect

[![FILAMENT 4.x](https://img.shields.io/badge/FILAMENT-4.x-EBB304?style=flat-square)](https://filamentphp.com/docs/4.x/panels/installation)
[![FILAMENT 5.x](https://img.shields.io/badge/FILAMENT-5.x-EBB304?style=flat-square)](https://filamentphp.com/docs/5.x/panels/installation)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/finity-labs/fin-modal-table-select.svg?style=flat-square)](https://packagist.org/packages/finity-labs/fin-modal-table-select)
[![Tests](https://github.com/finity-labs/fin-modal-table-select/actions/workflows/tests.yml/badge.svg)](https://github.com/finity-labs/fin-modal-table-select/actions/workflows/tests.yml)
[![Code Style](https://github.com/finity-labs/fin-modal-table-select/actions/workflows/style.yml/badge.svg)](https://github.com/finity-labs/fin-modal-table-select/actions/workflows/style.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/finity-labs/fin-modal-table-select.svg?style=flat-square)](https://packagist.org/packages/finity-labs/fin-modal-table-select)
[![License](https://img.shields.io/packagist/l/finity-labs/fin-modal-table-select.svg?style=flat-square)](https://packagist.org/packages/finity-labs/fin-modal-table-select)

A drop-in replacement for Filament's native `ModalTableSelect` that actually shows what you've selected. It supports five display modes: badges (default), table, infolist, form, and selection-only. The mode resolves automatically based on what you configure -- set table columns and it uses table mode, set an infolist schema and it uses infolist mode, configure nothing and it falls back to standard badges.

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- Filament v4 or v5

## Installation

```bash
composer require finity-labs/fin-modal-table-select
```

The package auto-registers its service provider via Laravel package discovery.

### Tailwind @source directive

If you're using a custom Filament theme, add the plugin views to your theme's CSS file so Tailwind picks up the classes:

```css
@source '../../../../vendor/finity-labs/fin-modal-table-select/resources/**/*.blade.php';
```

You only need this when your app uses a custom theme. If you're on Filament's default theme, skip this step.

### Publishing translations

```bash
php artisan vendor:publish --tag="fin-modal-table-select-translations"
```

English translations are included out of the box. You can publish and customize them, or add translations for other locales.

## Usage

```php
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use Filament\Tables\Columns\TextColumn;

ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->tableColumns([
        TextColumn::make('name'),
        TextColumn::make('slug'),
    ])
```

See [the full documentation](docs/modal-table-select.md) for all display modes and configuration options.

## Compatibility

| Package | Filament | PHP |
|---------|----------|-----|
| 1.x | 4.x / 5.x | 8.2+ |

## License

MIT
