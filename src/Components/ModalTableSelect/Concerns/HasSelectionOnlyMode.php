<?php

declare(strict_types=1);

namespace FinityLabs\FinComponents\Components\ModalTableSelect\Concerns;

use Closure;

trait HasSelectionOnlyMode
{
    protected bool|Closure $isSelectionOnly = false;

    protected bool|Closure $hasSelectionSummary = false;

    protected string|Closure|null $selectionSummaryLabel = null;

    /**
     * Enable selection-only mode.
     *
     * In this mode, the component acts purely as a picker --
     * no badges, table, infolist, or form are rendered for the
     * selected items. Other form components can read the selected
     * IDs via `$get('fieldName')` and react accordingly.
     */
    public function selectionOnly(bool|Closure $condition = true): static
    {
        $this->isSelectionOnly = $condition;

        return $this;
    }

    /**
     * Show a compact summary next to the select button
     * (e.g. "3 items selected") when in selection-only mode.
     *
     * Has no effect when selectionOnly is not enabled.
     */
    public function selectionSummary(bool|Closure $condition = true): static
    {
        $this->hasSelectionSummary = $condition;

        return $this;
    }

    /**
     * Customize the summary label displayed next to the select button.
     *
     * Receives the count as `:count` placeholder.
     * Falls back to the package translation if not set.
     */
    public function selectionSummaryLabel(string|Closure|null $label): static
    {
        $this->selectionSummaryLabel = $label;

        return $this;
    }

    public function getIsSelectionOnly(): bool
    {
        return (bool) $this->evaluate($this->isSelectionOnly);
    }

    public function getHasSelectionSummary(): bool
    {
        return (bool) $this->evaluate($this->hasSelectionSummary);
    }

    public function getSelectionSummaryLabel(int $count): string
    {
        $custom = $this->evaluate($this->selectionSummaryLabel);

        if ($custom !== null) {
            return str_replace(':count', (string) $count, $custom);
        }

        return trans_choice(
            'fin-components::modal-table-select.count',
            $count,
            ['count' => $count],
        );
    }
}
