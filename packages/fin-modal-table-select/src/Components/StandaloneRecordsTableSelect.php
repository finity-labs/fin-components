<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Components;

use Filament\Forms\Components\TableSelect;
use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
use FinityLabs\FinModalTableSelect\Livewire\StandaloneRecordsTableSelectComponent;
use Livewire\Livewire;

/**
 * The stock TableSelect field with one difference: it mounts our Livewire
 * component, which knows how to run the modal table over the array payload in
 * the table arguments. The mount body mirrors the parent's byte for byte —
 * the parent hardcodes its Livewire class, so this override is the only seam.
 */
class StandaloneRecordsTableSelect extends TableSelect
{
    public function toEmbeddedHtml(): string
    {
        $extraAttributes = $this->getExtraAttributes();
        $id = $this->getId();
        $statePath = $this->getStatePath();

        $properties = [
            'isDisabled' => $this->isDisabled(),
            'maxSelectableRecords' => $this->getMaxItems(),
            'model' => $this->getModel(),
            'record' => $this->getRecord(),
            'relationshipName' => $this->getRelationshipName(),
            'shouldIgnoreRelatedRecords' => $this->shouldIgnoreRelatedRecords(),
            'tableConfiguration' => base64_encode($this->getTableConfiguration()),
            'tableArguments' => $this->getTableArguments(),
            $this->applyStateBindingModifiers('wire:model') => $statePath,
        ];

        $livewireHtml = Livewire::mount(StandaloneRecordsTableSelectComponent::class, $properties, $this->getLivewireKey());

        $attributes = (new FilamentComponentAttributeBag)
            ->merge([
                'aria-labelledby' => "{$id}-label",
                'id' => $id,
                'role' => 'group',
            ], escape: false)
            ->merge($extraAttributes, escape: false);

        return $this->wrapEmbeddedHtml('<div '.$attributes->toHtml().'>'.$livewireHtml.'</div>', labelTag: 'div');
    }
}
