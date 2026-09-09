<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect;

use FinityLabs\FinModalTableSelect\Livewire\StandaloneRecordsTableSelectComponent;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FinModalTableSelectServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fin-modal-table-select';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        Livewire::component('fin-modal-table-select.table-select', StandaloneRecordsTableSelectComponent::class);
    }
}
