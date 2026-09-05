<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Tests\Fixtures;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use FinityLabs\FinCodex\FinCodexPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The default panel: id admin, path /admin, the web guard, FinCodexPlugin
 * registered with literal option values (the staff panel uses closures). The middleware stack is the one Filament's own panel provider
 * generator emits; SetUpPanel is prepended automatically as panel:admin.
 */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->pages([Dashboard::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class])
            ->plugin(
                FinCodexPlugin::make()
                    ->helpButtonRenderHook(PanelsRenderHook::TOPBAR_END)
                    ->shortcut('ctrl+/')
                    ->drawerWidth(480)
                    ->globalSearch(false)
                    ->navigationGroup('Help')
                    ->navigationSort(90)
                    ->articleResource('App\\Filament\\Resources\\AdminHelpArticleResource')
                    ->settingsPage('App\\Filament\\Pages\\AdminHelpSettings')
                    ->coveragePage('App\\Filament\\Pages\\AdminHelpCoverage')
                    ->policyNamespace('App\\Policies'),
            );
    }
}
