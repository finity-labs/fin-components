<p align="center">
    <img src="art/main-image.jpeg" alt="FinModalTableSelect" width="640">
</p>

<h1 align="center">FinModalTableSelect for Filament</h1>

<p align="center">
    <a href="https://filamentphp.com/docs/4.x/panels/installation"><img src="https://img.shields.io/badge/FILAMENT-4.x-EBB304?style=flat-square" alt="Filament 4.x"></a>
    <a href="https://filamentphp.com/docs/5.x/panels/installation"><img src="https://img.shields.io/badge/FILAMENT-5.x-EBB304?style=flat-square" alt="Filament 5.x"></a>
    <a href="https://packagist.org/packages/finity-labs/fin-modal-table-select"><img src="https://img.shields.io/packagist/v/finity-labs/fin-modal-table-select.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/finity-labs/fin-modal-table-select/actions/workflows/tests.yml"><img src="https://github.com/finity-labs/fin-modal-table-select/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
</p>

<p align="center">
    A drop-in replacement for Filament's native <code>ModalTableSelect</code> that shows what you've selected —<br>
    and can push the selection into the rest of your form.
</p>

The stock component renders the selection as badges or comma-separated text. This one adds:

- **Nine display modes** — a table with columns inherited from your modal's `tableConfiguration()`, a stacked list with per-item remove, a card grid, a thumbnail strip, per-record badge colors/icons, text lists, an infolist card, your own Blade view per item, or nothing at all (selection-only). The mode resolves automatically from what you configure.
- **`fillsFields()`** — pick a company, get its name and tax number filled in, let the user overwrite the phone.
- **`fillsRepeater()` + `SelectedItemsRepeater`** — pick products, get one editable row each (quantity, price). Re-picking never wipes what the user edited, and deleting a row deselects its record.
- **`standalone()`** — use the picker without an Eloquent relationship; selected IDs land in a JSON column.

Everything user-facing stays stock Filament — the modal, the row entries, the repeater. This package only wires them together. All display modes support dark mode out of the box, and 58 locales ship with the package.

## Screenshots

**Pick records in a full Filament table** — search, sort, and filter inside the modal; the selection summary badge sits right on the field label:

<p align="center">
    <img src="https://github.com/user-attachments/assets/612cbda6-0d9a-481c-8dca-e1acbe16cf77" alt="The modal table select open over the invoice form, with rows checked" width="880">
</p>

**The invoice flow** — every picked product becomes an editable repeater row (`fillsRepeater()` + `SelectedItemsRepeater`). Re-picking keeps your edited quantities and prices; deleting a row deselects the product:

<p align="center">
    <img src="https://github.com/user-attachments/assets/848af614-d15b-416e-9a27-e1dca0150917" alt="Table-layout repeater filled from the selection, with editable quantity and price" width="880">
</p>

**Prefilled invoice header** — `fillsFields()` fills read-only entries and an editable phone from the picked company:

<p align="center">
    <img src="https://github.com/user-attachments/assets/48ae5f2f-bab3-44c4-9c2b-ed8c183c6121" alt="Company picker prefilling name, tax number, and an editable phone" width="880">
</p>

**Nine ways to show the selection** — badges with `displayLimit()`, stacked lists with per-item remove, inherited-column tables, text lists, per-record badge colors, card grids, thumbnails, and your own Blade view per item:

<p align="center">
    <img src="https://github.com/user-attachments/assets/08c6e393-9d1a-4071-a1be-77b137c3f39c" alt="Badges with display limit, stacked list, and inherited-column table displays" width="49%">
    <img src="https://github.com/user-attachments/assets/d90cb4a5-c896-4181-b10c-afca0fca8b52" alt="Text lists, per-record badges, card grid, thumbnails, and custom item views" width="49%">
</p>

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
