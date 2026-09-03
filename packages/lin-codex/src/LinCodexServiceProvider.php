<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LinCodexServiceProvider extends PackageServiceProvider
{
    public static string $name = 'lin-codex';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile();
    }
}
