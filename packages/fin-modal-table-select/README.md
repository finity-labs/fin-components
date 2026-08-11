# FinModalTableSelect

[![FILAMENT 4.x](https://img.shields.io/badge/FILAMENT-4.x-EBB304?style=flat-square)](https://filamentphp.com/docs/4.x/panels/installation)
[![FILAMENT 5.x](https://img.shields.io/badge/FILAMENT-5.x-EBB304?style=flat-square)](https://filamentphp.com/docs/5.x/panels/installation)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/finity-labs/fin-modal-table-select.svg?style=flat-square)](https://packagist.org/packages/finity-labs/fin-modal-table-select)
[![Tests](https://github.com/finity-labs/fin-modal-table-select/actions/workflows/tests.yml/badge.svg)](https://github.com/finity-labs/fin-modal-table-select/actions/workflows/tests.yml)
[![Code Style](https://github.com/finity-labs/fin-modal-table-select/actions/workflows/style.yml/badge.svg)](https://github.com/finity-labs/fin-modal-table-select/actions/workflows/style.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/finity-labs/fin-modal-table-select.svg?style=flat-square)](https://packagist.org/packages/finity-labs/fin-modal-table-select)
[![License](https://img.shields.io/packagist/l/finity-labs/fin-modal-table-select.svg?style=flat-square)](https://packagist.org/packages/finity-labs/fin-modal-table-select)

A drop-in replacement for Filament's native `ModalTableSelect` that shows what you've selected — and can push the selection into the rest of your form.

- **Rich selected-items display**: a table (columns inherited from your modal's `tableConfiguration()` via `displayAsTable()`), a stacked list with per-item remove buttons, an infolist card for single selection, badges with a "+N more" cap (`displayLimit()`), or nothing (selection-only mode). The mode resolves automatically from what you configure.
- **`fillsFields()`**: selecting a record prefills sibling form fields — pick a company, get its name and tax number filled in, let the user overwrite the phone.
- **`fillsRepeater()`** + **`SelectedItemsRepeater`**: a multiple selection syncs into a sibling `Repeater` with merge semantics — pick products, get one editable row each (quantity, price), re-picking never wipes what the user already edited, and deleting a row deselects its record.
- **`standalone()`**: use the picker without an Eloquent relationship — records come from a model query and the selected IDs land in a JSON column.

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

// Selected records rendered as a table, columns inherited from CategoriesTable:
ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->displayAsTable()

// Invoice lines: pick products, edit quantity/price per row in a Repeater:
ModalTableSelect::make('product_picker')
    ->relationship('products', 'name')
    ->multiple()
    ->tableConfiguration(ProductsTable::class)
    ->selectionOnly()
    ->fillsRepeater('items', fn (Product $record): array => [
        'product_id' => $record->getKey(),
        'name'       => $record->name,
        'unit_price' => $record->default_price,
        'quantity'   => 1,
    ], keyAttribute: 'product_id')
```

See [the full documentation](docs/modal-table-select.md) for all display modes and configuration options.

## Compatibility

| Package | Filament | PHP |
|---------|----------|-----|
| 1.x | 4.x / 5.x | 8.2+ |

## License

MIT
