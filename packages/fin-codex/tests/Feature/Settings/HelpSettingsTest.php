<?php

use Filament\Facades\Filament;
use FinityLabs\FinCodex\Enums\NavigationGroup;
use FinityLabs\FinCodex\Pages\HelpSettings;
use FinityLabs\FinCodex\Tests\Fixtures\Pages\AdminHelpSettings;
use FinityLabs\FinCodex\Tests\Fixtures\Pages\StaffHelpSettings;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use Livewire\Livewire;

/*
 * SET-01: one screen where an admin adds a language, picks the default,
 * decides what a reader sees when a translation is missing and turns
 * revision history on or off.
 *
 * FinCodexPlugin::register() puts the page on every panel that carries the
 * plugin: the settingsPage() override when the host named one, the built-in
 * HelpSettings otherwise. Admin and staff register real fixture subclasses
 * (what a host does), portal keeps the built-in class, and the plain panel,
 * which has no plugin, gets nothing.
 *
 * One panel per test method for page requests: FilamentManager is scoped and
 * boots only the first panel of a PHP request cycle.
 */

/** A fixture user signed in on the given panel guard. */
function finCodexSettingsUser(string $guard = 'web'): User
{
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.com']);

    test()->actingAs($user, $guard);

    return $user;
}

it('registers the override on admin and staff and the built-in page on portal, nothing on plain', function (): void {
    expect(array_values(Filament::getPanel('admin')->getPages()))
        ->toContain(AdminHelpSettings::class)
        ->not->toContain(HelpSettings::class)
        ->and(array_values(Filament::getPanel('staff')->getPages()))
        ->toContain(StaffHelpSettings::class)
        ->not->toContain(HelpSettings::class)
        ->and(array_values(Filament::getPanel('portal')->getPages()))
        ->toContain(HelpSettings::class)
        ->and(array_values(Filament::getPanel('plain')->getPages()))
        ->not->toContain(HelpSettings::class)
        ->not->toContain(AdminHelpSettings::class)
        ->not->toContain(StaffHelpSettings::class);
});

it('answers on its own route for a signed-in admin', function (): void {
    finCodexSettingsUser();

    $url = route('filament.admin.pages.help-settings');

    expect($url)->toContain('/admin/help-settings');

    $this->get($url)->assertOk();
});

it('files one slot after the article resource on every panel that sets a sort', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(AdminHelpSettings::getNavigationGroup())->toBe('Help')
        ->and(AdminHelpSettings::getNavigationSort())->toBe(91);

    Filament::setCurrentPanel(Filament::getPanel('staff'));

    expect(StaffHelpSettings::getNavigationGroup())->toBe('Support')
        ->and(StaffHelpSettings::getNavigationSort())->toBe(6);

    Filament::setCurrentPanel(Filament::getPanel('portal'));

    expect(HelpSettings::getNavigationGroup())->toBe(NavigationGroup::Help)
        ->and(HelpSettings::getNavigationSort())->toBeNull();
});

it('reads its navigation label and title from the lang files and follows the locale', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $englishLabel = (string) __('fin-codex::fin-codex.settings.navigation');
    $englishTitle = (string) __('fin-codex::fin-codex.settings.title');

    expect(AdminHelpSettings::getNavigationLabel())->toBe($englishLabel)
        ->and((new AdminHelpSettings)->getTitle())->toBe($englishTitle);

    app()->setLocale('de');

    $germanLabel = (string) __('fin-codex::fin-codex.settings.navigation');
    $germanTitle = (string) __('fin-codex::fin-codex.settings.title');

    expect(AdminHelpSettings::getNavigationLabel())->toBe($germanLabel)
        ->not->toBe($englishLabel)
        ->and((new AdminHelpSettings)->getTitle())->toBe($germanTitle)
        ->not->toBe($englishTitle);
});

it('mounts with the five settings keys in its form state', function (): void {
    $this->usesPanel('admin', finCodexSettingsUser());

    $data = Livewire::test(AdminHelpSettings::class)
        ->assertOk()
        ->get('data');

    expect($data)->toBeArray()
        ->and(array_keys($data))->toEqualCanonicalizing([
            'languages',
            'default_locale',
            'fallback',
            'revisions_enabled',
            'revisions_keep',
        ]);
});
