<?php

declare(strict_types=1);

namespace FinityLabs\FinAvatar;

use FinityLabs\FinAvatar\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FinAvatarServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fin-avatar';

    /** {@inheritDoc} */
    public function configurePackage(Package $package): void
    {
        /**
         * @var Package $package
         */
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasRoute('web')
            ->hasConsoleCommand(InstallCommand::class);
    }
}
