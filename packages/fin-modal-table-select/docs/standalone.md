# Standalone mode (no relationship)

Use the picker against a model directly and store the selected primary keys in the field state — for JSON columns, wizard steps, or anywhere an Eloquent relationship doesn't fit. (Filament's native component requires a relationship; this mode removes that constraint.)

```php
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;

ModalTableSelect::make('device_ids')
    ->standalone(Device::class, 'name')              // model + title attribute for labels
    ->multiple()
    ->tableConfiguration(DevicesTable::class)
    ->displayAsTable()
```

## Two things to set up

**1. The modal table needs its own query.** There's no relationship to derive it from, so the `tableConfiguration()` class must provide one:

```php
class DevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(Device::query())
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('serial'),
            ]);
    }
}
```

**2. Cast the column on your model** so the ID array survives the round-trip to the database:

```php
protected function casts(): array
{
    return [
        'device_ids' => 'array',
    ];
}
```

## Scoping the query

`standaloneModifyQueryUsing()` scopes the query used for labels and selected-record loading (scope the modal's list inside the table configuration class):

```php
->standaloneModifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
```

## What works in standalone mode

Everything. All display modes, `fillsFields()`, `fillsRepeater()`, per-item remove, display limits — the only difference is where records come from. Labels resolve from the title attribute you pass to `standalone()` (or `getOptionLabelFromRecordUsing()` if you set one), falling back to the record key.

Single selection works too — the selected ID is stored as a plain value instead of an array:

```php
ModalTableSelect::make('device_id')
    ->standalone(Device::class, 'name')
    ->tableConfiguration(DevicesTable::class)
    ->infolistSchema([...])
```

## Array-backed records: standaloneRecords()

When the rows aren't Eloquent models at all — an application's named routes, its Filament page classes, entries from an external API — hand the picker a plain array:

```php
ModalTableSelect::make('route_names')
    ->tableConfiguration(RoutesTable::class)
    ->standaloneRecords(
        fn (Get $get): array => app(RouteCatalog::class)->rows($get('panel')),
        keyAttribute: 'key',                          // where each row carries its identity
        titleAttribute: 'label',                      // what badges and lists display
    )
    ->multiple()
```

How it behaves:

- **The records closure runs with Filament's closure dependency injection** on the field, inside the form — so `fn (Get $get) => ...` can scope the list by sibling state, including selects in the same Repeater row. It's re-evaluated when the modal opens and when the selection changes.
- **The modal table runs over the array** through Filament's `records()` data source: search covers the configuration's globally `searchable()` columns, sorting the `sortable()` ones, pagination included. The `tableConfiguration()` class defines columns and filters only — no `query()`.
- **State is the record keys as strings**, single or `multiple()`, exactly like the other modes — so `fillsRepeater()`, `hydrateSelectionFromRepeater()`, and `SelectedItemsRepeater` work unchanged.
- **Stale keys degrade gracefully**: a saved key the closure no longer returns renders as its raw key (a stand-in row carrying just the key attribute) rather than being dropped or throwing.
- **Every display mode accepts array records** — table (including `displayAsTable()` column inheritance), stacked list, cards, thumbnails, badges with per-record colors, infolist, custom item views. Per-record closures receive the row: `fn (array $record) => ...`.
- The list is embedded in the modal's Livewire component when it opens, so keep it at "hundreds of rows" scale — for tens of thousands, prefer a real table with `standalone(Model::class)`.
