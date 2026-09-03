<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FinCodexServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fin-codex';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name);
    }
}
