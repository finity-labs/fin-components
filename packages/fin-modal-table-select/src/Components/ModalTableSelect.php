<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\ModalTableSelect as FilamentModalTableSelect;
use Filament\Forms\Components\TableSelect;
use FinityLabs\FinModalTableSelect\Concerns\CanFillFields;
use FinityLabs\FinModalTableSelect\Concerns\CanFillRepeater;
use FinityLabs\FinModalTableSelect\Concerns\HasBadgeAndListDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasCardsDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasInfolistDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasItemViewDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasSelectionOnlyMode;
use FinityLabs\FinModalTableSelect\Concerns\HasStackedListDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasStandaloneMode;
use FinityLabs\FinModalTableSelect\Concerns\HasStandaloneRecords;
use FinityLabs\FinModalTableSelect\Concerns\HasTableDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasThumbnailsDisplay;
use FinityLabs\FinModalTableSelect\Enums\DisplayMode;
use FinityLabs\FinModalTableSelect\Livewire\StandaloneRecordsTableSelectComponent;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ModalTableSelect extends FilamentModalTableSelect
{
    use CanFillFields;
    use CanFillRepeater;
    use HasBadgeAndListDisplay;
    use HasCardsDisplay;
    use HasInfolistDisplay;
    use HasItemViewDisplay;
    use HasSelectionOnlyMode;
    use HasStackedListDisplay;
    use HasStandaloneMode;
    use HasStandaloneRecords;
    use HasTableDisplay;
    use HasThumbnailsDisplay;

    protected string $view = 'fin-modal-table-select::components.modal-table-select.modal-table-select';

    protected int|Closure|null $displayLimit = null;

    protected bool|Closure $hasEmptyStateSelectButton = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Move the select action to the label line as a hint action
        $this->selectAction(function (Action $action): Action {
            return $action->iconButton();
        });

        // Through the injected component, never $this: Filament clones a
        // field into every Repeater row and into an action's modal schema,
        // and a closure bound to the prototype would build the actions on a
        // component that belongs to no schema — its state, label and Get
        // all need the container.
        $this->registerActions([
            fn (ModalTableSelect $component): Action => $component->getCollapseToggleAction(),
            fn (ModalTableSelect $component): Action => $component->getRemoveSelectedItemAction(),
            fn (ModalTableSelect $component): Action => $component->getSelectAction(),
        ]);

        // Share an Alpine `open` flag across the whole field (label hint actions
        // and content) so the chevron hint action can show/hide the collapsible
        // table without a Livewire round-trip.
        $this->extraFieldWrapperAttributes(function (ModalTableSelect $component): array {
            if (! $component->getIsTableCollapsible() || $component->getDisplayMode() !== DisplayMode::Table) {
                return [];
            }

            return [
                'x-data' => '{ open: '.($component->getIsTableCollapsed() ? 'false' : 'true').' }',
            ];
        });
    }

    /**
     * @return array<Action>
     */
    public function getHintActions(): array
    {
        $actions = parent::getHintActions();

        if (! $this->isDisabled()) {
            $selectAction = $this->getAction('select');

            if ($selectAction) {
                array_unshift($actions, $selectAction);
            }
        }

        // Place the collapse chevron immediately before the select action.
        if ($this->shouldShowCollapseToggle()) {
            $toggleAction = $this->getAction('toggleTable');

            if ($toggleAction) {
                array_unshift($actions, $toggleAction);
            }
        }

        return $actions;
    }

    /**
     * A client-side-only hint action that shows/hides the collapsible table.
     * It flips the Alpine `open` flag set on the field wrapper, so there is no
     * Livewire round-trip and the chevron rotates in step with the table.
     */
    public function getCollapseToggleAction(): Action
    {
        return Action::make('toggleTable')
            ->label(__('fin-modal-table-select::modal-table-select.toggle'))
            ->icon('heroicon-m-chevron-down')
            ->iconButton()
            ->color('gray')
            ->alpineClickHandler('open = ! open')
            ->extraAttributes([
                // Drive the rotation inline so it does not depend on a
                // `rotate-180` utility being present in the consumer's build.
                'style' => 'transition: transform 200ms ease;',
                'x-bind:style' => "open ? 'transform: rotate(180deg)' : 'transform: rotate(0deg)'",
            ]);
    }

    /**
     * The collapse toggle only makes sense for a table display that is both
     * collapsible and actually rendering rows.
     */
    public function shouldShowCollapseToggle(): bool
    {
        return $this->getIsTableCollapsible()
            && $this->getDisplayMode() === DisplayMode::Table
            && filled($this->getState());
    }

    /**
     * Determine which display mode should be used for the selected items.
     *
     * Priority:
     *   1. SelectionOnly (if selectionOnly() is enabled)
     *   2. ItemView (if itemView() is set — the escape hatch wins)
     *   3. Table (if displayAsTable() or tableColumns()/tableSchema() configured)
     *   4. Cards (if cardGrid() is enabled)
     *   5. Thumbnails (if thumbnails() is set)
     *   6. StackedList (if stackedList() is enabled)
     *   7. Infolist (single selection with infolistSchema() configured)
     *   8. Badges (default, inherits parent behavior; listStyle() and
     *      per-record badge closures restyle this mode)
     */
    public function getDisplayMode(): DisplayMode
    {
        if ($this->getIsSelectionOnly()) {
            return DisplayMode::SelectionOnly;
        }

        if ($this->hasItemViewDisplay()) {
            return DisplayMode::ItemView;
        }

        if ($this->hasTableDisplay()) {
            return DisplayMode::Table;
        }

        if ($this->hasCardGridDisplay()) {
            return DisplayMode::Cards;
        }

        if ($this->hasThumbnailsDisplay()) {
            return DisplayMode::Thumbnails;
        }

        if ($this->hasStackedListDisplay()) {
            return DisplayMode::StackedList;
        }

        if ((! $this->isMultiple()) && $this->hasInfolistSchema()) {
            return DisplayMode::Infolist;
        }

        return DisplayMode::Badges;
    }

    /**
     * Check if a custom display mode is configured (non-default).
     */
    public function hasCustomDisplay(): bool
    {
        return in_array($this->getDisplayMode(), [
            DisplayMode::ItemView,
            DisplayMode::Table,
            DisplayMode::Cards,
            DisplayMode::Thumbnails,
            DisplayMode::StackedList,
            DisplayMode::Infolist,
        ], true);
    }

    /**
     * Render a "Select ..." link in the empty state that opens the modal, so
     * users are not left hunting for the icon on the label line.
     */
    public function emptyStateSelectButton(bool|Closure $condition = true): static
    {
        $this->hasEmptyStateSelectButton = $condition;

        return $this;
    }

    public function getHasEmptyStateSelectButton(): bool
    {
        return (bool) $this->evaluate($this->hasEmptyStateSelectButton);
    }

    /**
     * Resolve a per-record display value from an attribute path or a Closure
     * receiving the record. Shared by the stacked list, cards, thumbnails,
     * and per-record badge displays.
     */
    public function resolveRecordDisplayValue(Model|array $record, string|Closure|null $source): ?string
    {
        if ($source === null) {
            return null;
        }

        $value = $source instanceof Closure
            ? $this->evaluateWithRecord($source, $record)
            : data_get($record, $source);

        return filled($value) ? (string) $value : null;
    }

    /**
     * Like resolveRecordDisplayValue(), but an Htmlable result passes through
     * untouched instead of being cast, so it renders as HTML. For plain text
     * the two behave identically.
     *
     * @param  Model|array<string, mixed>  $record
     */
    public function resolveRecordDisplayHtml(Model|array $record, string|Closure|null $source): string|Htmlable|null
    {
        if ($source === null) {
            return null;
        }

        $value = $source instanceof Closure
            ? $this->evaluateWithRecord($source, $record)
            : data_get($record, $source);

        if ($value instanceof Htmlable) {
            return $value;
        }

        return filled($value) ? (string) $value : null;
    }

    /**
     * Evaluate a per-record closure with the record injected by the `record`
     * name; Model records also inject by type, so existing Model-typed
     * closures keep resolving exactly as before.
     *
     * @param  Model|array<string, mixed>  $record
     */
    public function evaluateWithRecord(Closure $closure, Model|array $record): mixed
    {
        return $this->evaluate($closure, [
            'record' => $record,
        ], $record instanceof Model ? [
            Model::class => $record,
        ] : []);
    }

    /**
     * The identity of a record across both worlds: the model key, or the
     * standaloneRecords() key attribute for array records.
     *
     * @param  Model|array<string, mixed>  $record
     */
    public function getRecordKey(Model|array $record): string
    {
        if ($record instanceof Model) {
            return (string) $record->getKey();
        }

        return (string) (data_get($record, $this->getStandaloneRecordsKeyAttribute()) ?? '');
    }

    /**
     * Cap how many items render before a "+N more" toggle appears. Applies to
     * the badges and stacked-list displays. Null shows everything.
     */
    public function displayLimit(int|Closure|null $limit): static
    {
        $this->displayLimit = $limit;

        return $this;
    }

    public function getDisplayLimit(): ?int
    {
        $limit = $this->evaluate($this->displayLimit);

        return $limit === null ? null : (int) $limit;
    }

    /**
     * A per-item action that removes one record from the selection without
     * reopening the modal. Invoked from the view with a recordKey argument.
     */
    public function getRemoveSelectedItemAction(): Action
    {
        return Action::make('removeSelectedItem')
            ->label(__('fin-modal-table-select::modal-table-select.remove'))
            ->icon('heroicon-m-x-mark')
            ->iconButton()
            ->color('gray')
            ->hidden(fn (): bool => $this->isDisabled())
            ->action(function (array $arguments): void {
                $this->removeSelectedItem($arguments['recordKey'] ?? null);
            });
    }

    public function removeSelectedItem(mixed $key): void
    {
        if ($key === null) {
            return;
        }

        $state = $this->getState();

        if (is_array($state)) {
            $this->state(array_values(array_filter(
                $state,
                fn ($id): bool => (string) $id !== (string) $key,
            )));
        } elseif ((string) $state === (string) $key) {
            $this->state(null);
        } else {
            return;
        }

        $this->callAfterStateUpdated();
    }

    /**
     * The best available human label for a record: the option-label callback
     * if configured (models only — the callback is Model-typed upstream),
     * then the mode's title attribute, then the record key.
     *
     * @param  Model|array<string, mixed>  $record
     */
    public function getRecordDisplayLabel(Model|array $record): string
    {
        if (($record instanceof Model) && $this->hasOptionLabelFromRecordUsingCallback()) {
            return (string) $this->getOptionLabelFromRecord($record);
        }

        $attribute = match (true) {
            $this->hasStandaloneRecords() => $this->getStandaloneRecordsTitleAttribute(),
            $this->getIsStandalone() => $this->getStandaloneTitleAttribute(),
            default => $this->getRelationshipTitleAttribute(),
        };

        if (filled($attribute)) {
            $value = data_get($record, str_replace('->', '.', $attribute));

            if (($record instanceof Model) || filled($value)) {
                return (string) $value;
            }
        }

        return $this->getRecordKey($record);
    }

    /**
     * In standaloneRecords() mode the modal table needs the array payload and
     * a Livewire component that knows how to serve it; the records closure is
     * evaluated HERE, on the field inside the form container, so it gets
     * Filament's closure dependency injection ($get and friends) — the modal
     * child component itself has no access to the form's state.
     *
     * @return array<mixed>
     */
    public function getTableArguments(): array
    {
        $arguments = parent::getTableArguments();

        if ($this->hasStandaloneRecords()) {
            $arguments[StandaloneRecordsTableSelectComponent::RECORDS_ARGUMENT] = array_values($this->getStandaloneRecordsIndex());
            $arguments[StandaloneRecordsTableSelectComponent::KEY_ATTRIBUTE_ARGUMENT] = $this->getStandaloneRecordsKeyAttribute();
        }

        return $arguments;
    }

    /**
     * Mirror of the parent's builder, swapping in the TableSelect subclass
     * that mounts our records-aware Livewire component. Model modes take the
     * parent's path untouched.
     */
    public function getTableSelect(): TableSelect
    {
        if (! $this->hasStandaloneRecords()) {
            return parent::getTableSelect();
        }

        $select = StandaloneRecordsTableSelect::make('selection')
            ->label($this->getLabel())
            ->hiddenLabel()
            ->tableConfiguration($this->getTableConfiguration())
            ->relationshipName($this->getRelationshipName())
            ->multiple($this->isMultiple())
            ->maxItems($this->getMaxItems())
            ->tableArguments($this->getTableArguments());

        if ($this->modifyTableSelectUsing) {
            $select = $this->evaluate(
                $this->modifyTableSelectUsing,
                namedInjections: [
                    'select' => $select,
                    'tableSelect' => $select,
                ],
                typedInjections: [
                    TableSelect::class => $select,
                ],
            ) ?? $select;
        }

        return $select;
    }
}
