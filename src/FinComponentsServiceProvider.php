<?php

declare(strict_types=1);

namespace FinityLabs\FinComponents;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FinComponentsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fin-components';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasViews();
    }
}
