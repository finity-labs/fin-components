<?php

declare(strict_types=1);

namespace FinityLabs\FinComponents\Components\ModalTableSelect;

use Filament\Forms\Components\ModalTableSelect as FilamentModalTableSelect;
use FinityLabs\FinComponents\Components\ModalTableSelect\Concerns\HasFormDisplay;
use FinityLabs\FinComponents\Components\ModalTableSelect\Concerns\HasInfolistDisplay;
use FinityLabs\FinComponents\Components\ModalTableSelect\Concerns\HasSelectionOnlyMode;
use FinityLabs\FinComponents\Components\ModalTableSelect\Concerns\HasTableDisplay;
use FinityLabs\FinComponents\Components\ModalTableSelect\Enums\DisplayMode;

class ModalTableSelect extends FilamentModalTableSelect
{
    use HasFormDisplay;
    use HasInfolistDisplay;
    use HasSelectionOnlyMode;
    use HasTableDisplay;

    protected string $view = 'fin-components::components.modal-table-select.modal-table-select';

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

        if ($this->isMultiple()) {
            if ($this->hasTableColumns()) {
                return DisplayMode::Table;
            }

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
