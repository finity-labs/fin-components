<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\ModalTableSelect as FilamentModalTableSelect;
use Filament\Schemas\Components\Text;
use FinityLabs\FinModalTableSelect\Concerns\CanFillFields;
use FinityLabs\FinModalTableSelect\Concerns\CanFillRepeater;
use FinityLabs\FinModalTableSelect\Concerns\HasBadgeAndListDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasCardsDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasInfolistDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasItemViewDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasSelectionOnlyMode;
use FinityLabs\FinModalTableSelect\Concerns\HasStackedListDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasStandaloneMode;
use FinityLabs\FinModalTableSelect\Concerns\HasTableDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasThumbnailsDisplay;
use FinityLabs\FinModalTableSelect\Enums\DisplayMode;
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

        $this->registerActions([
            fn (): Action => $this->getCollapseToggleAction(),
            fn (): Action => $this->getRemoveSelectedItemAction(),
            fn (): Action => $this->getSelectAction(),
        ]);

        // Count badge on the label line, right after the field label. Opt-in
        // via selectionSummary(); renders nothing while the selection is empty.
        $this->afterLabel(function (): ?Text {
            if (! $this->getHasSelectionSummary()) {
                return null;
            }

            $state = $this->getState();

            if (blank($state)) {
                return null;
            }

            $count = is_array($state) ? count($state) : 1;

            return Text::make($this->getSelectionSummaryLabel($count))
                ->badge()
                ->color('gray');
        });

        // Share an Alpine `open` flag across the whole field (label hint actions
        // and content) so the chevron hint action can show/hide the collapsible
        // table without a Livewire round-trip.
        $this->extraFieldWrapperAttributes(function (): array {
            if (! $this->getIsTableCollapsible() || $this->getDisplayMode() !== DisplayMode::Table) {
                return [];
            }

            return [
                'x-data' => '{ open: '.($this->getIsTableCollapsed() ? 'false' : 'true').' }',
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
    public function resolveRecordDisplayValue(Model $record, string|Closure|null $source): ?string
    {
        if ($source === null) {
            return null;
        }

        if ($source instanceof Closure) {
            $value = $this->evaluate($source, [
                'record' => $record,
            ], [
                Model::class => $record,
            ]);
        } else {
            $value = data_get($record, $source);
        }

        return filled($value) ? (string) $value : null;
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
     * if configured, then the relationship or standalone title attribute,
     * then the record key.
     */
    public function getRecordDisplayLabel(Model $record): string
    {
        if ($this->hasOptionLabelFromRecordUsingCallback()) {
            return (string) $this->getOptionLabelFromRecord($record);
        }

        $attribute = $this->getIsStandalone()
            ? $this->getStandaloneTitleAttribute()
            : $this->getRelationshipTitleAttribute();

        if (filled($attribute)) {
            return (string) data_get($record, str_replace('->', '.', $attribute));
        }

        return (string) $record->getKey();
    }
}
