<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Clusters\FinMailSettings;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use FinityLabs\FinMail\FinMailPlugin;
use UnitEnum;

class FinMailSettings extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $slug = 'mail-settings';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function getNavigationSort(): ?int
    {
        /** @var FinMailPlugin $plugin */
        $plugin = filament('fin-mail');

        return $plugin->getSettingsNavigationSort();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        /** @var FinMailPlugin $plugin */
        $plugin = filament('fin-mail');

        return $plugin->getSettingsNavigationGroup();
    }

    public static function getNavigationLabel(): string
    {
        return __('fin-mail::fin-mail.navigation.settings');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('fin-mail::fin-mail.settings.title');
    }
}
