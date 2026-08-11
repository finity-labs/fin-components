# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Added
- `listStyle()`: comma, dot, bullet, or line-break text lists instead of badges
- `badgeColorFromRecord()` / `badgeIconFromRecord()`: per-record badge colors and icons
- `cardGrid()`: card grid display with image, title, description, and remove buttons
- `thumbnails()`: compact image strip with tooltips, initials fallback, optional remove
- `itemView()`: render each selected record with a custom Blade view (receives $record, $field, $removeAction)
- `emptyStateSelectButton()`: "Select…" link in the empty state that opens the modal
- `standalone()`: use the picker without an Eloquent relationship — records come from a model query, selected IDs live in the field state (JSON column)
- `stackedList()`: stacked-list display with primary/secondary lines, thumbnail, and per-item remove buttons that update state server-side
- `displayLimit()`: collapse badges and stacked-list rows behind a client-side "+N more" toggle
- `SelectedItemsRepeater`: Repeater preset paired to a picker via `for()` — rows only enter through the modal, and deleting a row deselects its record
- `displayAsTable()`: table display for selected records with columns inherited from the modal's `tableConfiguration()` class
- `fillsFields()`: prefill sibling form fields from the selected record (attribute paths, dot notation, or closures); targets clear on deselection
- `fillsRepeater()`: sync a multiple selection into a sibling Repeater — new records append, user-edited rows survive, deselected rows are removed
- Livewire feature tests covering rendering, prefill, and repeater merge

### Changed
- Table and infolist rendering moved out of Blade into PHP schema builders; rows and cards are bound to the record models, so entry formatting, casts, and dot-notation relationships resolve natively
- `getSelectedRecords()` (was `getRecords()`) uses the parent's relationship query strategy, honors `modifyRelationshipQueryUsing()`, keeps selection order, and memoizes per state
- The parent's `getSelectedRecord()` pipeline and cache are used as-is instead of being overridden

### Removed
- Form display mode (`formSchema()`, `formColumns()`, `formEagerLoad()`): the rendered fields could never round-trip user input. Use `fillsFields()` with real form fields instead.
