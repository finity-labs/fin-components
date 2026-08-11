# Getting started

`FinityLabs\FinModalTableSelect\Components\ModalTableSelect` extends Filament's native `ModalTableSelect`, so everything you know carries over: `relationship()`, `tableConfiguration()`, `multiple()`, `selectAction()`, `tableArguments()`, validation, and so on. The package adds two things on top:

1. **Display modes** — the selection can render as a table, cards, a stacked list, thumbnails, styled badges or text lists, an infolist card, your own Blade view, or nothing at all.
2. **Fills** — the selection can push data into the rest of the form: prefill sibling fields from a single record, or sync a multiple selection into a Repeater.

Everything user-facing stays stock Filament: the modal is Filament's table select, table rows are infolist entries, the repeater is Filament's own. This package only wires them together.

## How the display mode is chosen

You never set a mode explicitly — it resolves from what you configure, in this priority order:

| Mode | Activated by |
|------|--------------|
| `SelectionOnly` | `selectionOnly()` |
| `ItemView` | `itemView()` — the escape hatch wins over everything below |
| `Table` | `displayAsTable()`, `tableColumns()`, or `tableSchema()` |
| `Cards` | `cardGrid()` |
| `Thumbnails` | `thumbnails()` |
| `StackedList` | `stackedList()` |
| `Infolist` | single selection with `infolistSchema()` |
| `Badges` | nothing configured — stock behavior; `listStyle()` and the per-record badge closures restyle this mode |

All modes are covered with examples in [Display modes](display-modes.md).

## The 30-second tour

```php
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;

// Same table inside and outside the modal — one extra method call:
ModalTableSelect::make('products')
    ->relationship('products', 'name')
    ->multiple()
    ->tableConfiguration(ProductsTable::class)
    ->displayAsTable()

// Pick a company, prefill the invoice header, let the user override:
ModalTableSelect::make('company_id')
    ->relationship('company', 'name')
    ->tableConfiguration(CompaniesTable::class)
    ->fillsFields([
        'company_name' => 'name',
        'tax_number'   => 'tax_number',
        'phone'        => 'contact.phone',
    ])

// Pick products, edit quantity and price per row:
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

## Where to next

- [Display modes](display-modes.md) — every way to render the selection
- [Filling form fields](filling-forms.md) — single record → sibling fields
- [Filling a repeater](repeater.md) — multiple records → editable rows
- [Standalone mode](standalone.md) — no relationship, IDs in a JSON column
- [Reference](reference.md) — full API table and translations
