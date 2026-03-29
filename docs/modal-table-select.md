# ModalTableSelect

Extends Filament's native `ModalTableSelect` with five display modes for showing selected items: table, infolist, form, selection-only, and the default badges.

```php
use FinityLabs\FinComponents\Components\ModalTableSelect\ModalTableSelect;
```

## Quick Start

The simplest upgrade from standard `ModalTableSelect` -- add `tableColumns()` to show selections in a table:

```php
use FinityLabs\FinComponents\Components\ModalTableSelect\ModalTableSelect;
use Filament\Tables\Columns\TextColumn;

ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->tableColumns([
        TextColumn::make('name'),
        TextColumn::make('slug'),
    ])
```

That's it. Selected items now render as a table instead of badges.

## Table Display

For multiple selections. Configure which columns appear in the selected items table.

### Basic usage

```php
ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->tableColumns([
        TextColumn::make('name'),
        TextColumn::make('slug'),
    ])
```

### Striped rows

```php
ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->tableColumns([
        TextColumn::make('name'),
        TextColumn::make('slug'),
    ])
    ->tableStriped()
```

### Eager loading

Load relationships upfront to avoid N+1 queries in your columns:

```php
ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->tableColumns([
        TextColumn::make('name'),
        TextColumn::make('parent.name')->label('Parent'),
    ])
    ->tableEagerLoad(['parent', 'tags'])
```

### Custom empty message

```php
ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->tableColumns([
        TextColumn::make('name'),
    ])
    ->tableEmptyMessage('No categories selected yet.')
```

### API reference

| Method | Signature | Description |
|--------|-----------|-------------|
| `tableColumns()` | `array\|Closure $columns` | Columns for the selected items table |
| `tableStriped()` | `bool\|Closure $condition = true` | Enable striped rows |
| `tableEmptyMessage()` | `string\|Closure\|null $message` | Custom empty state message |
| `tableEagerLoad()` | `array\|Closure $relationships` | Eager load relationships for selected records |

## Infolist Display

For single selections. Show the selected record using Filament infolist entries.

### Basic usage

```php
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

ModalTableSelect::make('category_id')
    ->relationship('category', 'name')
    ->tableConfiguration(CategoriesTable::class)
    ->infolistSchema([
        TextEntry::make('name'),
        TextEntry::make('slug'),
        TextEntry::make('description')->columnSpanFull(),
        IconEntry::make('is_active')->boolean(),
    ])
```

### Grid columns

Lay out entries in a multi-column grid:

```php
ModalTableSelect::make('category_id')
    ->relationship('category', 'name')
    ->tableConfiguration(CategoriesTable::class)
    ->infolistSchema([
        TextEntry::make('name'),
        TextEntry::make('slug'),
        TextEntry::make('description')->columnSpanFull(),
    ])
    ->infolistColumns(2)
```

### Eager loading

```php
ModalTableSelect::make('post_id')
    ->relationship('post', 'title')
    ->tableConfiguration(PostsTable::class)
    ->infolistSchema([
        TextEntry::make('title'),
        TextEntry::make('author.name')->label('Author'),
    ])
    ->infolistEagerLoad(['author'])
```

### API reference

| Method | Signature | Description |
|--------|-----------|-------------|
| `infolistSchema()` | `array\|Closure $schema` | Infolist entries for the selected record |
| `infolistColumns()` | `int\|Closure $columns` | Grid columns for layout (default: 1) |
| `infolistEagerLoad()` | `array\|Closure $relationships` | Eager load relationships for the selected record |

## Form Display

For single selections. Render the selected record using disabled form fields -- a read-only preview.

### Basic usage

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

ModalTableSelect::make('category_id')
    ->relationship('category', 'name')
    ->tableConfiguration(CategoriesTable::class)
    ->formSchema([
        TextInput::make('name'),
        TextInput::make('slug'),
        Toggle::make('is_active')->label('Active'),
    ])
```

Form fields are automatically set to `disabled()`, so they render as read-only. You don't need to add `->disabled()` yourself.

### Grid columns

```php
ModalTableSelect::make('category_id')
    ->relationship('category', 'name')
    ->tableConfiguration(CategoriesTable::class)
    ->formSchema([
        TextInput::make('name'),
        TextInput::make('slug'),
    ])
    ->formColumns(2)
```

### Eager loading

```php
ModalTableSelect::make('product_id')
    ->relationship('product', 'name')
    ->tableConfiguration(ProductsTable::class)
    ->formSchema([
        TextInput::make('name'),
        TextInput::make('category.name')->label('Category'),
    ])
    ->formEagerLoad(['category'])
```

### API reference

| Method | Signature | Description |
|--------|-----------|-------------|
| `formSchema()` | `array\|Closure $schema` | Form fields to display (rendered disabled) |
| `formColumns()` | `int\|Closure $columns` | Grid columns for layout (default: 1) |
| `formEagerLoad()` | `array\|Closure $relationships` | Eager load relationships for the selected record |

## Selection Only Mode

Use the component purely as a picker. No table, infolist, form, or badges are rendered for the selected items. Other form components can read the selected IDs via `$get()` and handle their own display logic.

### Multiple selection with summary

```php
use FinityLabs\FinComponents\Components\ModalTableSelect\ModalTableSelect;
use Filament\Forms\Components\Placeholder;

ModalTableSelect::make('category_ids')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->selectionOnly()
    ->selectionSummary()
    ->live(),

// Other components react to the selection
Placeholder::make('category_info')
    ->content(function ($get) {
        $ids = $get('category_ids') ?? [];

        return count($ids) . ' categories will be assigned.';
    }),
```

### Single selection with downstream fields

When picking a single record, you can populate other form fields using `afterStateUpdated`:

```php
ModalTableSelect::make('author_id')
    ->relationship('author', 'name')
    ->tableConfiguration(AuthorsTable::class)
    ->selectionOnly()
    ->selectionSummary()
    ->live()
    ->afterStateUpdated(function ($state, $set) {
        if ($state) {
            $author = \App\Models\Author::find($state);
            $set('author_email', $author?->email);
            $set('author_bio', $author?->bio);
        }
    }),

TextInput::make('author_email')->disabled(),
TextInput::make('author_bio')->disabled(),
```

### Custom summary label

The summary badge shows "X items selected" by default. Customize it with the `:count` placeholder:

```php
ModalTableSelect::make('tags')
    ->relationship('tags', 'name')
    ->multiple()
    ->tableConfiguration(TagsTable::class)
    ->selectionOnly()
    ->selectionSummary()
    ->selectionSummaryLabel(':count tags chosen')
```

### API reference

| Method | Signature | Description |
|--------|-----------|-------------|
| `selectionOnly()` | `bool\|Closure $condition = true` | Enable selection-only mode -- no display of selected items |
| `selectionSummary()` | `bool\|Closure $condition = true` | Show a count badge next to the select button |
| `selectionSummaryLabel()` | `string\|Closure\|null $label` | Custom label for the summary (`:count` placeholder) |

## Display Mode Resolution

The component picks a display mode automatically based on what you've configured. Here's the priority:

| Relationship | Configuration | Resulting Display |
|---|---|---|
| any | `selectionOnly()` | Button only (+ optional count badge) |
| `multiple()` | `tableColumns()` | Table |
| `multiple()` | *(none)* | Badges (default) |
| single | `infolistSchema()` | Infolist |
| single | `formSchema()` | Form |
| single | *(none)* | Text/Badge (default) |

`selectionOnly()` is checked first, regardless of relationship type. After that, for multiple selections the component looks for `tableColumns()`. For single selections, it checks `infolistSchema()` first, then `formSchema()`. If nothing is configured, you get the standard Filament behavior.

## Using Closures

All configuration methods accept closures for dynamic values:

```php
ModalTableSelect::make('items')
    ->relationship('items', 'name')
    ->multiple()
    ->tableConfiguration(ItemsTable::class)
    ->tableColumns(fn () => [
        TextColumn::make('name'),
        TextColumn::make('price')->money('EUR'),
    ])
    ->tableEagerLoad(fn () => ['category', 'brand'])
```

This works the same way across every method -- `infolistSchema()`, `formSchema()`, `tableStriped()`, and so on. Closures receive the standard Filament component evaluation context.

## Combining with Standard Features

All parent `ModalTableSelect` features work as normal. Mix them with display mode configuration:

```php
use Filament\Actions\Action;

ModalTableSelect::make('categories')
    ->relationship('categories', 'name')
    ->multiple()
    ->tableConfiguration(CategoriesTable::class)
    ->selectAction(
        fn (Action $action) => $action
            ->label('Choose categories')
            ->modalHeading('Browse categories')
    )
    ->getOptionLabelFromRecordUsing(
        fn (Category $record) => "{$record->name} ({$record->slug})"
    )
    ->tableColumns([
        TextColumn::make('name'),
        TextColumn::make('slug'),
    ])
```

## Fallback Behavior

If you don't configure any display mode, the component behaves exactly like Filament's standard `ModalTableSelect` -- badges for multiple selections, text or badge for single.

## Translations

The package ships with English translations. Publish and customize them:

```bash
php artisan vendor:publish --tag="fin-components-translations"
```

Translation keys live in `resources/lang/en/modal-table-select.php`:

| Key | Default |
|-----|---------|
| `empty_message` | No items selected. |
| `count` | :count item selected\|:count items selected |
| `remove` | Remove |
| `actions` | Actions |

## Known Limitations

- The selected items table uses a styled HTML table with Filament CSS classes, not a full Filament `Table` component. There's no sorting, filtering, or pagination on the selected items display.
- Infolist and form rendering create standalone instances. Complex Livewire-dependent components (like file uploads) in the form schema may not work as expected.
- Row actions in the selected table are limited to "remove". Custom row actions aren't supported yet.
