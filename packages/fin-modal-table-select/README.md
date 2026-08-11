# FinModalTableSelect

[![FILAMENT 4.x](https://img.shields.io/badge/FILAMENT-4.x-EBB304?style=flat-square)](https://filamentphp.com/docs/4.x/panels/installation)
[![FILAMENT 5.x](https://img.shields.io/badge/FILAMENT-5.x-EBB304?style=flat-square)](https://filamentphp.com/docs/5.x/panels/installation)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/finity-labs/fin-modal-table-select.svg?style=flat-square)](https://packagist.org/packages/finity-labs/fin-modal-table-select)
[![Tests](https://github.com/finity-labs/fin-modal-table-select/actions/workflows/tests.yml/badge.svg)](https://github.com/finity-labs/fin-modal-table-select/actions/workflows/tests.yml)

A drop-in replacement for Filament's native `ModalTableSelect` that shows what you've selected — and can push the selection into the rest of your form.

The stock component renders the selection as badges or comma-separated text. This one adds:

- **Nine display modes** — a table with columns inherited from your modal's `tableConfiguration()`, a stacked list with per-item remove, a card grid, a thumbnail strip, per-record badge colors/icons, text lists, an infolist card, your own Blade view per item, or nothing at all (selection-only). The mode resolves automatically from what you configure.
- **`fillsFields()`** — pick a company, get its name and tax number filled in, let the user overwrite the phone.
- **`fillsRepeater()` + `SelectedItemsRepeater`** — pick products, get one editable row each (quantity, price). Re-picking never wipes what the user edited, and deleting a row deselects its record.
- **`standalone()`** — use the picker without an Eloquent relationship; selected IDs land in a JSON column.

Everything user-facing stays stock Filament — the modal, the row entries, the repeater. This package only wires them together.

## Quick example

```php
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;

// Selected records as a table, columns inherited from the modal:
ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->displayAsTable()

// Invoice lines: pick products, edit quantity/price per row:
ModalTableSelect::make('product_picker')
    ->standalone(Product::class, 'name')
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

## Installation

```bash
composer require finity-labs/fin-modal-table-select
```

Custom Filament theme? Add the package views to your theme's `@source` list — see [Installation](docs/installation.md).

## Documentation

| Guide | Covers |
|-------|--------|
| [Installation](docs/installation.md) | Requirements, Tailwind setup, translations |
| [Getting started](docs/getting-started.md) | Concepts, display-mode resolution, 30-second tour |
| [Display modes](docs/display-modes.md) | Table, stacked list, cards, thumbnails, badges, lists, infolist, custom views |
| [Filling form fields](docs/filling-forms.md) | `fillsFields()` — prefill an invoice header from a company |
| [Filling a repeater](docs/repeater.md) | `fillsRepeater()` + `SelectedItemsRepeater` — the invoice-lines flow |
| [Standalone mode](docs/standalone.md) | Using the picker without a relationship |
| [Reference](docs/reference.md) | Full API table, public methods, translation keys |

## Compatibility

| Package | Filament | PHP |
|---------|----------|-----|
| 1.x | 4.x / 5.x | 8.2+ |

## License

MIT
