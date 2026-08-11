<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;

class SelectedItemsRepeater extends Repeater
{
    protected string|Closure|null $pickerName = null;

    protected string|Closure|null $pickerKeyAttribute = null;

    /**
     * Pair this repeater with a ModalTableSelect picker (usually one using
     * fillsRepeater() to target this repeater). Rows can only be added
     * through the picker, and deleting a row deselects its record on the
     * picker so the two stay in sync.
     *
     * The key attribute defaults to the picker's fillsRepeater() keyAttribute.
     */
    public function for(string|Closure $pickerName, string|Closure|null $keyAttribute = null): static
    {
        $this->pickerName = $pickerName;
        $this->pickerKeyAttribute = $keyAttribute;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->addable(false);

        $this->deleteAction(function (Action $action): Action {
            return $action->after(function (SelectedItemsRepeater $component): void {
                $component->syncPickerAfterDelete();
            });
        });
    }

    /**
     * Push the remaining rows' record keys back into the picker's state, so a
     * deleted row does not reappear the next time the modal is submitted.
     */
    public function syncPickerAfterDelete(): void
    {
        $picker = $this->getPicker();

        if (! $picker) {
            return;
        }

        $keyAttribute = $this->evaluate($this->pickerKeyAttribute)
            ?? $picker->getFillsRepeaterKeyAttribute();

        $remaining = collect($this->getState() ?? [])
            ->map(fn ($row): mixed => data_get($row, $keyAttribute))
            ->filter(fn ($key): bool => filled($key))
            ->values()
            ->all();

        $picker->state($remaining);
    }

    public function getPicker(): ?ModalTableSelect
    {
        $pickerName = $this->evaluate($this->pickerName);

        if (blank($pickerName)) {
            return null;
        }

        $field = $this->getRootContainer()->getFlatFields(withHidden: true)[$pickerName] ?? null;

        return ($field instanceof ModalTableSelect) ? $field : null;
    }
}
