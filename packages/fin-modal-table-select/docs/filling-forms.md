# Filling form fields

`fillsFields()` copies data from the selected record into sibling fields whenever the selection changes. The targets are ordinary form components — they validate, dehydrate, and save like any other field, and the user can overwrite what was filled in.

The invoice case: pick a company, show its name and tax number, let the user adjust the phone number.

```php
use Filament\Forms\Components\TextInput;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;

ModalTableSelect::make('company_id')
    ->relationship('company', 'name')
    ->tableConfiguration(CompaniesTable::class)
    ->fillsFields([
        'company_name' => 'name',                    // attribute path
        'tax_number'   => 'tax_number',
        'phone'        => 'contact.phone',           // dot notation works
        'greeting'     => fn (Company $record): string => "Dear {$record->owner_name}",
    ]),

TextInput::make('company_name')->disabled()->dehydrated(),
TextInput::make('tax_number')->disabled()->dehydrated(),
TextInput::make('phone'),                            // prefilled, user can edit
```

## How it behaves

- **Deselecting** sets every target to `null`.
- **The hook stacks** on `afterStateUpdated()`, so your own `afterStateUpdated()` callbacks keep working — Filament runs them all.
- **Single selection only** — it does nothing when `multiple()` is enabled. For multiple selection you want [a repeater fill](repeater.md).
- **Targets are relative paths**, the same way `$set()` resolves them: siblings by plain name.
- The record is re-resolved fresh when the fill runs, so it always reflects the just-changed selection.

## Field choice tips

- Prefilled-but-locked values: `TextInput::make(...)->disabled()->dehydrated()` — visible, not editable, still saved.
- Prefilled-and-editable values: a plain `TextInput` — the fill writes once per selection change; anything the user types afterwards stays.
- A dropdown of the selected record's relations (e.g. the company's contacts): a normal `Select` reading the picker's state:

```php
Select::make('contact_id')
    ->options(fn (Get $get): array => Company::find($get('company_id'))
        ?->contacts()->pluck('name', 'id')->all() ?? []),
```
