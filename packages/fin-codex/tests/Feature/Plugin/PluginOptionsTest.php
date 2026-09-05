<?php

use Filament\Facades\Filament;
use Filament\View\PanelsRenderHook;
use FinityLabs\FinCodex\Enums\NavigationGroup;
use FinityLabs\FinCodex\FinCodexPlugin;

/** @return array<string, mixed> Every option as the panel reports it. */
function pluginOptions(FinCodexPlugin $plugin): array
{
    return [
        'helpButtonRenderHook' => $plugin->getHelpButtonRenderHook(),
        'shortcut' => $plugin->getShortcut(),
        'drawerWidth' => $plugin->getDrawerWidth(),
        'globalSearch' => $plugin->hasGlobalSearch(),
        'navigationGroup' => $plugin->getNavigationGroup(),
        'navigationSort' => $plugin->getNavigationSort(),
        'articleResource' => $plugin->getArticleResource(),
        'settingsPage' => $plugin->getSettingsPage(),
        'coveragePage' => $plugin->getCoveragePage(),
        'policyNamespace' => $plugin->getPolicyNamespace(),
    ];
}

/** @return array<string, mixed> The literal values AdminPanelProvider sets. */
function adminOptions(): array
{
    return [
        'helpButtonRenderHook' => PanelsRenderHook::TOPBAR_END,
        'shortcut' => 'ctrl+/',
        'drawerWidth' => 480,
        'globalSearch' => false,
        'navigationGroup' => 'Help',
        'navigationSort' => 90,
        'articleResource' => 'App\\Filament\\Resources\\AdminHelpArticleResource',
        'settingsPage' => 'App\\Filament\\Pages\\AdminHelpSettings',
        'coveragePage' => 'App\\Filament\\Pages\\AdminHelpCoverage',
        'policyNamespace' => 'App\\Policies',
    ];
}

/** @return array<string, mixed> What StaffPanelProvider's closures evaluate to. */
function staffOptions(): array
{
    return [
        'helpButtonRenderHook' => PanelsRenderHook::TOPBAR_START,
        'shortcut' => 'ctrl+.',
        'drawerWidth' => 360,
        'globalSearch' => true,
        'navigationGroup' => 'Support',
        'navigationSort' => 5,
        'articleResource' => 'Staff\\Filament\\Resources\\StaffHelpArticleResource',
        'settingsPage' => 'Staff\\Filament\\Pages\\StaffHelpSettings',
        'coveragePage' => 'Staff\\Filament\\Pages\\StaffHelpCoverage',
        'policyNamespace' => 'Staff\\Policies',
    ];
}

it('starts from the documented defaults on a fresh instance', function (): void {
    expect(pluginOptions(FinCodexPlugin::make()))->toBe([
        'helpButtonRenderHook' => PanelsRenderHook::TOPBAR_END,
        'shortcut' => 'ctrl+/',
        'drawerWidth' => 480,
        'globalSearch' => false,
        'navigationGroup' => NavigationGroup::Help,
        'navigationSort' => null,
        'articleResource' => null,
        'settingsPage' => null,
        'coveragePage' => null,
        'policyNamespace' => 'App\\Policies',
    ])->and(FinCodexPlugin::make())->not->toBe(FinCodexPlugin::make());
});

it('returns itself from every setter and evaluates closures in the getters', function (): void {
    $plugin = FinCodexPlugin::make();

    expect($plugin->shortcut(fn (): string => 'alt+h'))->toBe($plugin)
        ->and($plugin->globalSearch(fn (): bool => true))->toBe($plugin)
        ->and($plugin->drawerWidth(fn (): int => 600))->toBe($plugin)
        ->and($plugin->getShortcut())->toBe('alt+h')
        ->and($plugin->hasGlobalSearch())->toBeTrue()
        ->and($plugin->getDrawerWidth())->toBe(600)
        ->and($plugin->shortcut(null)->getShortcut())->toBeNull();
});

it('reads each panel\'s own values through filament(\'fin-codex\')', function (string $panel, array $expected): void {
    Filament::setCurrentPanel(Filament::getPanel($panel));

    $plugin = filament('fin-codex');

    expect($plugin)->toBeInstanceOf(FinCodexPlugin::class)
        ->and($plugin)->toBe(FinCodexPlugin::get())
        ->and(pluginOptions($plugin))->toBe($expected);
})->with([
    'admin (literal values)' => ['admin', adminOptions()],
    'staff (closure values)' => ['staff', staffOptions()],
]);

it('gives every option a different value on the two panels', function (): void {
    foreach (array_keys(adminOptions()) as $option) {
        expect(adminOptions()[$option])->not->toBe(staffOptions()[$option], "option {$option} must differ between admin and staff");
    }
});

it('throws for a panel without the plugin', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('plain'));

    expect(fn () => filament('fin-codex'))->toThrow(LogicException::class);
});
