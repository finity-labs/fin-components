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
