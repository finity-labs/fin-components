<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Components;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\ModalTableSelect as FilamentModalTableSelect;
use FinityLabs\FinModalTableSelect\Concerns\HasFormDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasInfolistDisplay;
use FinityLabs\FinModalTableSelect\Concerns\HasSelectionOnlyMode;
use FinityLabs\FinModalTableSelect\Concerns\HasTableDisplay;
use FinityLabs\FinModalTableSelect\Enums\DisplayMode;

class ModalTableSelect extends FilamentModalTableSelect
{
    use HasFormDisplay;
    use HasInfolistDisplay;
    use HasSelectionOnlyMode;
    use HasTableDisplay;

    protected string $view = 'fin-modal-table-select::components.modal-table-select.modal-table-select';

    protected function setUp(): void
    {
        parent::setUp();

        // Move the select action to the label line as a hint action
        $this->selectAction(function (\Filament\Actions\Action $action): \Filament\Actions\Action {
            return $action->iconButton();
        });

        $this->registerActions([
            fn (): \Filament\Actions\Action => $this->getSelectAction(),
        ]);
    }

    /**
     * @return array<\Filament\Actions\Action>
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

        return $actions;
    }

    /**
     * Determine which display mode should be used for the selected items.
     *
     * Priority:
     *   0. SelectionOnly (if selectionOnly() is enabled)
     *
     * For multiple:
     *   1. Table (if tableColumns configured)
     *   2. Badges (default)
     *
     * For single:
     *   1. Infolist (if infolistSchema configured)
     *   2. Form (if formSchema configured)
     *   3. Badges (default)
     */
    public function getDisplayMode(): DisplayMode
    {
        if ($this->getIsSelectionOnly()) {
            return DisplayMode::SelectionOnly;
        }

        if ($this->hasTableColumns()) {
            return DisplayMode::Table;
        }

        if ($this->isMultiple()) {
            return DisplayMode::Badges;
        }

        if ($this->hasInfolistSchema()) {
            return DisplayMode::Infolist;
        }

        if ($this->hasFormSchema()) {
            return DisplayMode::Form;
        }

        return DisplayMode::Badges;
    }

    /**
     * Check if a custom display mode is configured (non-default).
     */
    public function hasCustomDisplay(): bool
    {
        return in_array($this->getDisplayMode(), [
            DisplayMode::Table,
            DisplayMode::Infolist,
            DisplayMode::Form,
        ], true);
    }
}
