# ModalTableSelect

A drop-in replacement for Filament's `ModalTableSelect` that shows what you've selected — and can push the selection into the rest of your form.

The stock component displays selected records as badges or plain text. This one adds three things on top:

- **Rich display** of selected records: a table (with columns inherited from your modal's table configuration), an infolist card, or nothing at all (selection-only mode).
- **`fillsFields()`** — selecting a record prefills sibling form fields. The user can overwrite them afterwards.
- **`fillsRepeater()`** — selecting records syncs them into a sibling Repeater, preserving values the user already edited.

Everything user-facing stays stock Filament: the modal is Filament's table select, the rows are infolist entries, the repeater is Filament's own. This package only wires them together.

## Display modes

The mode resolves automatically from what you configure:

| Mode | When |
|------|------|
| `SelectionOnly` | `selectionOnly()` is enabled |
| `Table` | `displayAsTable()`, `tableColumns()`, or `tableSchema()` is set |
| `StackedList` | `stackedList()` is enabled |
| `Infolist` | single selection with `infolistSchema()` set |
| `Badges` | nothing configured — parent behavior, including `badge()` / `badgeColor()` |

### Table display

The fastest way to a table is to reuse the columns your modal already has:

```php
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;

ModalTableSelect::make('products')
    ->relationship('products', 'name')
    ->multiple()
    ->tableConfiguration(ProductsTable::class)
    ->displayAsTable()
```

`displayAsTable()` derives the selected-items table from `ProductsTable` — same columns inside and outside the modal, zero extra configuration. Text columns become text entries (badges carry over), image columns become image entries, icon columns become icon entries. Hidden columns are skipped.

When you want different columns outside the modal, define them explicitly:

```php
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;

ModalTableSelect::make('products')
    ->relationship('products', 'name')
    ->multiple()
    ->tableConfiguration(ProductsTable::class)
    ->tableColumns([
        TableColumn::make('Name'),
        TableColumn::make('Price'),
    ])
    ->tableSchema([
        TextEntry::make('name'),
        TextEntry::make('default_price')->money('eur'),
    ])
```

Rows are real infolist entries bound to the record models, so `badge()`, `date()`, `money()`, enum casts, and dot-notation relationship paths (`category.name`) all work as usual.

Options:

```php
->tableEagerLoad(['category'])                       // avoid N+1 for relationship entries
->tableModifyQueryUsing(fn ($query) => $query->withSum('items', 'qty'))
->tableEmptyMessage('Nothing picked yet.')
->tableFooterCount()                                 // "3 items selected" under the table
->tableCollapsible()                                 // show/hide toggle on the label line
->tableCollapsed()                                   // start collapsed
```

Selected records keep their selection order, and the query runs once per state — repeated renders reuse the memoized result.

### Infolist display

For single selection, render the chosen record as a read-only card:

```php
use Filament\Infolists\Components\TextEntry;

ModalTableSelect::make('company_id')
    ->relationship('company', 'name')
    ->tableConfiguration(CompaniesTable::class)
    ->infolistSchema([
        TextEntry::make('name'),
        TextEntry::make('tax_number'),
        TextEntry::make('contact.phone'),
    ])
    ->infolistColumns(2)
    ->infolistEagerLoad(['contact'])
```

The record resolves through the parent component's pipeline, so `getSelectedRecordUsing()` and the built-in record cache are respected.

### Selection-only mode

A headless picker: nothing renders for the selection except an optional count badge. Useful when other components react to the selected IDs via `$get()`, or together with `fillsFields()` / `fillsRepeater()`.

```php
ModalTableSelect::make('device_ids')
    ->relationship('devices', 'name')
    ->multiple()
    ->tableConfiguration(DevicesTable::class)
    ->selectionOnly()
    ->selectionSummary()
    ->selectionSummaryLabel(':count devices')
```

### Stacked list display

A middle ground between badges and a full table: one row per record with a primary line, an optional secondary line and round thumbnail, and a remove button — so users can drop an item without reopening the modal. Removing an item updates the state server-side and reruns `fillsFields()` / `fillsRepeater()`.

```php
ModalTableSelect::make('assignees')
    ->relationship('assignees', 'name')
    ->multiple()
    ->tableConfiguration(UsersTable::class)
    ->stackedList()
    ->stackedListPrimary('name')                     // default: option label
    ->stackedListSecondary('email')
    ->stackedListImage('avatar_url')
    ->stackedListRemovable(false)                    // hide the remove buttons
```

Primary, secondary, and image accept an attribute path or a closure receiving the record. Primary falls back to the option label (relationship title attribute, standalone title attribute, or record key).

### Display limit

`displayLimit(int)` caps how many items render before a "+N more" toggle. It applies to badges and the stacked list, and expands client-side — no round-trip.

```php
->stackedList()
->displayLimit(5)                                    // 8 selected: 5 shown, "+3 more"
```

### Standalone mode (no relationship)

Use the picker against a model directly and store the selected primary keys in the field state — for JSON columns, wizard steps, or anywhere an Eloquent relationship doesn't fit.

```php
ModalTableSelect::make('device_ids')
    ->standalone(Device::class, 'name')              // model + title attribute for labels
    ->multiple()
    ->tableConfiguration(DevicesTable::class)
    ->displayAsTable()
```

Two things to know:

- The modal table has no relationship to query, so your `tableConfiguration()` class must set its own query: `$table->query(Device::query())`.
- Cast the column on your model (`'device_ids' => 'array'`) so the ID array survives the round-trip to the database.

`standaloneModifyQueryUsing(fn ($query) => ...)` scopes the query used for labels and selected-record loading. All display modes and both fills features work the same as in relationship mode.

## Filling form fields

`fillsFields()` copies data from the selected record into sibling fields whenever the selection changes. The targets are ordinary form components — they validate, dehydrate, and save like any other field, and the user can overwrite what was filled in.

The invoice case: pick a company, show its name and tax number, let the user adjust the phone number.

```php
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;

ModalTableSelect::make('company_id')
    ->relationship('company', 'name')
    ->tableConfiguration(CompaniesTable::class)
    ->fillsFields([
        'company_name' => 'name',                    // attribute path
        'tax_number'   => 'tax_number',
        'phone'        => 'contact.phone',           // dot notation works
        'greeting'     => fn (Company $record): string => "Dear {$record->owner_name}",
    ]),

TextEntry::make('company_name'),
TextEntry::make('tax_number'),
TextInput::make('phone'),                            // prefilled, user can edit
```

Deselecting sets every target to `null`. The hook stacks on `afterStateUpdated()`, so your own `afterStateUpdated()` callbacks keep working. Intended for single selection — it does nothing when `multiple()` is enabled.

## Filling a repeater

`fillsRepeater()` syncs a multiple selection into a sibling Repeater. This is the invoice-lines flow: pick products in the modal, get one editable row per product.

```php
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;

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
    ->dehydrated(false),

Repeater::make('items')
    ->table([
        TableColumn::make('Product'),
        TableColumn::make('Unit price'),
        TableColumn::make('Qty'),
    ])
    ->schema([
        Hidden::make('product_id'),
        TextInput::make('name')->disabled()->dehydrated(),
        TextInput::make('unit_price')->numeric(),
        TextInput::make('quantity')->numeric(),
    ])
    ->addable(false),                                // rows enter via the modal
```

The merge is what makes this worth packaging. When the user reopens the modal and changes the selection:

- newly selected records are appended as fresh rows built by your closure
- rows whose record is still selected are left untouched — edited quantities and prices survive
- rows whose record was deselected are removed

Rows are matched to records through `keyAttribute` (the row key holding the record's primary key). If your closure doesn't include it, it's added automatically.

### SelectedItemsRepeater

A `Repeater` subclass that pairs with the picker so the two stay in sync in both directions. It ships `addable(false)` (rows enter through the modal), and deleting a row deselects the record on the picker — without it, a deleted product would reappear the next time the user confirms the modal.

```php
use FinityLabs\FinModalTableSelect\Components\SelectedItemsRepeater;

SelectedItemsRepeater::make('items')
    ->for('product_picker')                          // the picker's field name
    ->table([...])
    ->schema([...])
```

The record key attribute is read from the picker's `fillsRepeater()` configuration; pass a second argument to `for()` to override it. Don't call `deleteAction()` on it yourself — that would replace the sync hook.

## API reference

| Method | Parameters | Description |
|--------|-----------|-------------|
| `displayAsTable()` | `bool\|Closure $condition = true` | Table display with columns inherited from `tableConfiguration()` |
| `tableColumns()` | `array\|Closure $columns` | Header columns (`RepeatableEntry\TableColumn`) for the selected-items table |
| `tableSchema()` | `array\|Closure $schema` | Infolist entries rendered per row |
| `tableEagerLoad()` | `array\|Closure $relationships` | Relationships to eager-load for table display |
| `tableModifyQueryUsing()` | `?Closure $callback` | Modify the selected-records query (aggregates etc.) |
| `tableEmptyMessage()` | `string\|Closure\|null $message` | Empty-state text |
| `tableFooterCount()` | `bool\|Closure $condition = true` | Row-count footer under the table |
| `tableCollapsible()` | `bool\|Closure $condition = true` | Show/hide toggle on the label line |
| `tableCollapsed()` | `bool\|Closure $condition = true` | Start collapsed (needs `tableCollapsible()`) |
| `infolistSchema()` | `array\|Closure $schema` | Entries for the single-record infolist card |
| `infolistColumns()` | `int\|Closure $columns` | Grid columns for the infolist card |
| `infolistEagerLoad()` | `array\|Closure $relationships` | Relationships to eager-load for the infolist record |
| `selectionOnly()` | `bool\|Closure $condition = true` | Headless picker, no selection display |
| `selectionSummary()` | `bool\|Closure $condition = true` | Count badge in selection-only mode |
| `selectionSummaryLabel()` | `string\|Closure\|null $label` | Custom summary text, `:count` placeholder |
| `fillsFields()` | `array\|Closure $map` | Fill sibling fields from the selected record |
| `stackedList()` | `bool\|Closure $condition = true` | Stacked-list display with per-item remove |
| `stackedListPrimary()` | `string\|Closure\|null $source` | Primary line (attribute path or closure) |
| `stackedListSecondary()` | `string\|Closure\|null $source` | Secondary line |
| `stackedListImage()` | `string\|Closure\|null $source` | Thumbnail image URL |
| `stackedListRemovable()` | `bool\|Closure $condition = true` | Toggle the remove buttons |
| `displayLimit()` | `int\|Closure\|null $limit` | "+N more" cap for badges and stacked list |
| `standalone()` | `class-string\|Closure $model, string\|Closure\|null $titleAttribute = null` | Relationship-free mode |
| `standaloneModifyQueryUsing()` | `?Closure $callback` | Scope the standalone query |
| `fillsRepeater()` | `string\|Closure $repeaterName, Closure $itemUsing, string\|Closure $keyAttribute = 'id'` | Sync selection into a sibling Repeater |

Everything the parent supports (`relationship()`, `tableConfiguration()`, `multiple()`, `badge()`, `badgeColor()`, `selectAction()`, `tableArguments()`, ...) works unchanged.

## Translations

Published under the `fin-modal-table-select` namespace:

```bash
php artisan vendor:publish --tag="fin-modal-table-select-translations"
```

Keys: `empty_message`, `count` (pluralized), `remove`, `actions`, `toggle`, `more` (pluralized), `less`. English ships with the package.
