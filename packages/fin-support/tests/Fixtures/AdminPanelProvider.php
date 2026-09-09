<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures;

use Filament\Http\Middleware\Authenticate;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use FinityLabs\FinSupport\Tests\Fixtures\Pages\GatedPage;
use FinityLabs\FinSupport\Tests\Fixtures\Pages\ShieldedPage;
use FinityLabs\FinSupport\Tests\Fixtures\Pages\UnregisteredShieldedPage;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->pages([Dashboard::class, GatedPage::class, ShieldedPage::class, UnregisteredShieldedPage::class])
            ->plugin(FixturePlugin::make()->policyNamespace('Fixture\\Policies'))
            ->middleware(['web'])
            ->authMiddleware([Authenticate::class]);
    }
}
