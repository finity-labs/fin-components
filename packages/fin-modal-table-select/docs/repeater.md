# Filling a repeater

The invoice-lines flow: pick products in a modal table, get one editable row per product. Two pieces work together — `fillsRepeater()` on the picker, and the `SelectedItemsRepeater` preset for the rows.

## The complete recipe

```php
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Components\SelectedItemsRepeater;

ModalTableSelect::make('product_picker')
    ->standalone(Product::class, 'name')             // or ->relationship(...)
    ->multiple()
    ->tableConfiguration(ProductsTable::class)
    ->selectionOnly()                                // the repeater IS the display
    ->selectionSummary()
    ->fillsRepeater('items', fn (Product $record): array => [
        'product_id' => $record->getKey(),
        'name'       => $record->name,
        'unit_price' => $record->default_price,
        'quantity'   => 1,
    ], keyAttribute: 'product_id')
    ->dehydrated(false),                             // rows carry the data; the picker is UI state

SelectedItemsRepeater::make('items')
    ->for('product_picker')
    ->table([
        TableColumn::make('Product'),
        TableColumn::make('Unit price'),
        TableColumn::make('Qty'),
    ])
    ->schema([
        Hidden::make('product_id'),
        TextInput::make('name')->disabled()->dehydrated(),
        TextInput::make('unit_price')->numeric(),
        TextInput::make('quantity')->numeric()->minValue(1),
    ]),
```

## Merge semantics

The merge is what makes this worth packaging. When the user reopens the modal and changes the selection:

- newly selected records are appended as fresh rows built by your closure
- rows whose record is still selected are left untouched — edited quantities and prices survive
- rows whose record was deselected are removed

Rows are matched to records through `keyAttribute` (the row key holding the record's primary key). If your closure doesn't include it in the returned row, it's added automatically.

## SelectedItemsRepeater

A `Repeater` subclass that keeps the pair in sync in both directions:

- `addable(false)` by default — rows only enter through the modal
- deleting a row deselects its record on the picker — without this, a deleted product would reappear the next time the user confirms the modal
- the record key attribute is read from the picker's `fillsRepeater()` configuration; pass a second argument to `for()` to override it

```php
SelectedItemsRepeater::make('items')
    ->for('product_picker')                          // picker field name (sibling)
```

Don't call `deleteAction()` on it yourself — that would replace the sync hook. Everything else from `Repeater` (table layout, `reorderable()`, validation, `columns()`) works normally.

## Edit pages: restoring the picker selection

The picker is `dehydrated(false)`, so its selection isn't saved — the rows are. On edit pages, rebuild the selection from the saved rows so the modal opens with the right records pre-checked:

```php
ModalTableSelect::make('product_picker')
    // ...
    ->hydrateSelectionFromRepeater(),
```

The repeater name and key attribute default to the `fillsRepeater()` configuration; pass them explicitly to override. The hook only runs when the picker state is empty, so a saved selection is never clobbered.

## Alternative: picker as its own display

Nothing forces `selectionOnly()`. If you want a rich confirmation of what's selected *and* editable rows, combine a display mode with the repeater fill — for example `stackedList()` above the repeater. Removing an item from the stacked list also removes its row, because the remove action reruns the fills.
