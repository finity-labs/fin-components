# Reference

Everything the parent supports (`relationship()`, `tableConfiguration()`, `multiple()`, `badge()`, `badgeColor()`, `selectAction()`, `tableArguments()`, `maxItems()`, ...) works unchanged. The tables below list what this package adds.

## Display

| Method | Parameters | Description |
|--------|-----------|-------------|
| `displayAsTable()` | `bool\|Closure $condition = true` | Table display with columns inherited from `tableConfiguration()` |
| `tableColumns()` | `array\|Closure $columns` | Header columns (`RepeatableEntry\TableColumn`) for the selected-items table |
| `tableSchema()` | `array\|Closure $schema` | Infolist entries rendered per row |
| `tableEagerLoad()` | `array\|Closure $relationships` | Relationships to eager-load for table display |
| `tableModifyQueryUsing()` | `?Closure $callback` | Modify the selected-records query (aggregates etc.) |
| `tableEmptyMessage()` | `string\|Closure\|null $message` | Empty-state text (used by all display modes) |
| `tableFooterCount()` | `bool\|Closure $condition = true` | Row-count footer under the table |
| `tableCollapsible()` | `bool\|Closure $condition = true` | Show/hide toggle on the label line |
| `tableCollapsed()` | `bool\|Closure $condition = true` | Start collapsed (needs `tableCollapsible()`) |
| `stackedList()` | `bool\|Closure $condition = true` | Stacked-list display with per-item remove |
| `stackedListPrimary()` | `string\|Closure\|null $source` | Primary line (attribute path or closure) |
| `stackedListSecondary()` | `string\|Closure\|null $source` | Secondary line |
| `stackedListImage()` | `string\|Closure\|null $source` | Thumbnail image URL |
| `stackedListRemovable()` | `bool\|Closure $condition = true` | Toggle the remove buttons |
| `cardGrid()` | `bool\|Closure $condition = true` | Card grid display |
| `cardTitle()` / `cardDescription()` / `cardImage()` | `string\|Closure\|null $source` | Card content |
| `cardColumns()` | `int\|Closure $columns` | Grid columns (default 3) |
| `cardsRemovable()` | `bool\|Closure $condition = true` | Toggle card remove buttons |
| `thumbnails()` | `string\|Closure $imageSource` | Thumbnail strip display |
| `thumbnailsSquare()` | `bool\|Closure $condition = true` | Rounded squares instead of circles |
| `thumbnailsRemovable()` | `bool\|Closure $condition = true` | Remove button per thumbnail |
| `listStyle()` | `ListStyle\|Closure\|null $style` | Comma / dot / bullet / line-break text list |
| `badgeColorFromRecord()` | `?Closure $callback` | Per-record badge color |
| `badgeIconFromRecord()` | `?Closure $callback` | Per-record badge icon |
| `infolistSchema()` | `array\|Closure $schema` | Entries for the single-record infolist card |
| `infolistColumns()` | `int\|Closure $columns` | Grid columns for the infolist card |
| `infolistEagerLoad()` | `array\|Closure $relationships` | Relationships to eager-load for the infolist record |
| `itemView()` | `string\|Closure\|null $view, array\|Closure $viewData = []` | Custom Blade view per record |
| `selectionOnly()` | `bool\|Closure $condition = true` | Headless picker, no selection display |
| `selectionSummary()` | `bool\|Closure $condition = true` | Count badge on the label line, any display mode |
| `selectionSummaryLabel()` | `string\|Closure\|null $label` | Custom summary text, `:count` placeholder |
| `displayLimit()` | `int\|Closure\|null $limit` | "+N more" cap for badges, stacked list, cards, thumbnails |
| `emptyStateSelectButton()` | `bool\|Closure $condition = true` | "Select…" link in the empty state |

## Data and fills

| Method | Parameters | Description |
|--------|-----------|-------------|
| `standalone()` | `class-string\|Closure $model, string\|Closure\|null $titleAttribute = null` | Relationship-free mode |
| `standaloneModifyQueryUsing()` | `?Closure $callback` | Scope the standalone query |
| `fillsFields()` | `array\|Closure $map` | Fill sibling fields from the selected record |
| `fillsRepeater()` | `string\|Closure $repeaterName, Closure $itemUsing, string\|Closure $keyAttribute = 'id'` | Sync selection into a sibling Repeater |
| `hydrateSelectionFromRepeater()` | `string\|Closure\|null $repeaterName = null, string\|Closure\|null $keyAttribute = null` | On edit pages, rebuild the picker selection from saved rows (defaults from `fillsRepeater()`) |

## FilledEntry

| Method | Parameters | Description |
|--------|-----------|-------------|
| `FilledEntry::make()` | `string $name, string\|Closure\|null $label = null, ?Closure $modifyEntryUsing = null` | A fillsFields() display target: Hidden field + live TextEntry pair |

## SelectedItemsRepeater

| Method | Parameters | Description |
|--------|-----------|-------------|
| `for()` | `string\|Closure $pickerName, string\|Closure\|null $keyAttribute = null` | Pair with a picker; delete syncs back, `addable(false)` by default |

## Useful public methods

Handy inside closures, custom views, and tests:

| Method | Returns | Description |
|--------|---------|-------------|
| `getDisplayMode()` | `DisplayMode` | The resolved display mode |
| `getSelectedRecords()` | `EloquentCollection` | Selected models, selection order, memoized per state |
| `getSelectedRecord()` | `?Model` | Single selected model (parent pipeline, cached) |
| `getRecordDisplayLabel($record)` | `string` | Best available label for a record |
| `removeSelectedItem($key)` | `void` | Drop one record from the selection and rerun fills |

## Translations

Published under the `fin-modal-table-select` namespace:

```bash
php artisan vendor:publish --tag="fin-modal-table-select-translations"
```

| Key | Used for |
|-----|----------|
| `empty_message` | Empty-state text |
| `count` (pluralized) | Footer count / selection summary |
| `remove` | Per-item remove action label |
| `toggle` | Collapse chevron label |
| `more` (pluralized) | "+N more" overflow toggle |
| `less` | "Show less" overflow toggle |
| `select` | Empty-state select button |
