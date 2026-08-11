# Display modes

Every way the selection can render on the form. The mode resolves automatically — see [Getting started](getting-started.md#how-the-display-mode-is-chosen) for the priority order.

## Table

The flagship. The fastest way to a table is to reuse the columns your modal already has:

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

## Stacked list

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

## Card grid

For visual records — image on top, title, optional description, remove button in the corner:

```php
ModalTableSelect::make('properties')
    ->relationship('properties', 'name')
    ->multiple()
    ->tableConfiguration(PropertiesTable::class)
    ->cardGrid()
    ->cardImage('cover_url')                         // path or fn ($record)
    ->cardTitle('name')                              // default: option label
    ->cardDescription('address')
    ->cardColumns(3)
    ->cardsRemovable(false)
```

## Thumbnail strip

The most compact visual display: a row of small images, record label as tooltip, initials as fallback when a record has no image. Good for media pickers and avatar-style selections.

```php
ModalTableSelect::make('members')
    ->relationship('members', 'name')
    ->multiple()
    ->tableConfiguration(MembersTable::class)
    ->thumbnails('avatar_url')
    ->thumbnailsSquare()                             // rounded squares instead of circles
    ->thumbnailsRemovable()                          // small x on each thumbnail
```

## Badges and text lists

The default mode, upgraded. Per-record badge colors and icons (the selection renders from records instead of plain labels as soon as either closure is set):

```php
ModalTableSelect::make('tasks')
    ->relationship('tasks', 'title')
    ->multiple()
    ->tableConfiguration(TasksTable::class)
    ->badgeColorFromRecord(fn (Task $record): string => $record->status->color())
    ->badgeIconFromRecord(fn (Task $record): string => $record->status->icon())
```

Or drop badges for a plain text list in one of four styles:

```php
use FinityLabs\FinModalTableSelect\Enums\ListStyle;

->listStyle(ListStyle::Comma)        // Alpha, Beta, Gamma
->listStyle(ListStyle::Dot)          // Alpha · Beta · Gamma
->listStyle(ListStyle::Bullet)       // bulleted <ul>
->listStyle(ListStyle::LineBreak)    // one per line
```

## Infolist card

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

## Custom item view

The escape hatch: render each selected record with your own Blade view. It receives `$record`, `$field`, and `$removeAction` (a pre-bound per-item remove action — echo it or ignore it).

```php
->itemView('components.selected-product', ['showSku' => true])
```

```blade
{{-- resources/views/components/selected-product.blade.php --}}
<div class="flex items-center gap-2">
    <span>{{ $record->name }}</span>
    @if ($showSku) <span class="text-gray-500">{{ $record->sku }}</span> @endif
    {{ $removeAction }}
</div>
```

## Selection-only

A headless picker: nothing renders for the selection except an optional count badge. Useful when other components react to the selected IDs via `$get()`, or together with the [fills features](repeater.md).

```php
ModalTableSelect::make('device_ids')
    ->relationship('devices', 'name')
    ->multiple()
    ->tableConfiguration(DevicesTable::class)
    ->selectionOnly()
    ->selectionSummary()
    ->selectionSummaryLabel(':count devices')
```

## Shared options

**Display limit.** `displayLimit(int)` caps how many items render before a "+N more" toggle. It applies to badges, the stacked list, cards, and thumbnails, and expands client-side — no round-trip.

```php
->stackedList()
->displayLimit(5)                                    // 8 selected: 5 shown, "+3 more"
```

**Empty-state select button.** `emptyStateSelectButton()` renders a "Select…" link under the empty-state text that opens the modal — handy when the icon on the label line is easy to miss. Works in every display mode.

**Per-item remove.** The stacked list, cards, thumbnails, and custom item views all support removing one record without reopening the modal. Remove buttons hide automatically when the field is disabled.
