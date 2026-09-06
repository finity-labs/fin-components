<?php

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use FinityLabs\FinCodex\Enums\NavigationGroup;
use FinityLabs\FinCodex\Pages\HelpSettings;
use FinityLabs\FinCodex\Resources\ArticleResource\Schemas\TranslationTabs;
use FinityLabs\FinCodex\Tests\Fixtures\Pages\AdminHelpSettings;
use FinityLabs\FinCodex\Tests\Fixtures\Pages\StaffHelpSettings;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Enums\FallbackBehaviour;
use FinityLabs\LinCodex\Revisions\RevisionManager;
use FinityLabs\LinCodex\Settings\CodexSettings;
use Livewire\Features\SupportTesting\Testable;
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

/**
 * Store a whole settings group, the way a host that has already saved once
 * looks. The harness seeds the group in defineDatabaseMigrations(), so this
 * overwrites rather than creates.
 *
 * @param  list<string>  $codes
 */
function finCodexSettingsSeed(
    array $codes = ['en', 'de'],
    string $default = 'en',
    FallbackBehaviour $fallback = FallbackBehaviour::ShowDefault,
    bool $revisions = true,
    int $keep = 7,
): CodexSettings {
    $settings = app(CodexSettings::class);
    $settings->languages = array_map([CodexSettings::class, 'languageEntry'], $codes);
    $settings->default_locale = $default;
    $settings->fallback = $fallback;
    $settings->revisions_enabled = $revisions;
    $settings->revisions_keep = $keep;
    $settings->save();

    return $settings;
}

/**
 * The settings as the database holds them right now.
 *
 * app(CodexSettings::class) is a fresh instance per resolve in a package
 * install, but the page's fresh-install guard binds one into the container for
 * the rest of the request, so a post-save read has to drop it first.
 */
function finCodexSettingsStored(): CodexSettings
{
    app()->forgetInstance(CodexSettings::class);

    return app(CodexSettings::class);
}

/**
 * A schema component of the mounted page, found by its state path. The page
 * sets no keys, so getComponent() is given a callback rather than a key.
 */
function finCodexSettingsComponent(Testable $page, string $statePath): ?Component
{
    return $page->instance()->getSchema('form')?->getComponent(
        fn (Component $component): bool => $component->getStatePath() === $statePath,
    );
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

/*
 * SET-01's substance, against a seeded settings group.
 */

it('opens on the stored settings', function (): void {
    finCodexSettingsSeed();

    $this->usesPanel('admin', finCodexSettingsUser());

    $data = Livewire::test(AdminHelpSettings::class)->get('data');

    // The repeater hydrates as a uuid-keyed map and dehydrates back to a list,
    // so the rows are read through array_values() and the keys are uuids.
    expect(array_values($data['languages']))->toBe([
        ['code' => 'en', 'display' => 'English', 'flag-icon' => 'gb'],
        ['code' => 'de', 'display' => 'Deutsch', 'flag-icon' => 'de'],
    ])
        ->and(array_keys($data['languages']))->each->toMatch('/^[0-9a-f-]{36}$/')
        ->and($data['default_locale'])->toBe('en')
        // The enum arrives as a scalar: toArray() hands over a FallbackBehaviour
        // instance and the Select normalises it to its backing value as a string.
        ->and($data['fallback'])->toBe('1')
        ->and($data['revisions_enabled'])->toBeTrue()
        // TextInput::numeric() casts on the way in too, so a stored int 7 is a
        // float in form state. mutateFormDataBeforeSave() casts it back.
        ->and($data['revisions_keep'])->toEqual(7)
        ->and($data['revisions_keep'])->toBeFloat();
});

it('round-trips a save into a real enum case and an int keep count', function (): void {
    finCodexSettingsSeed();

    $this->usesPanel('admin', finCodexSettingsUser());

    $page = Livewire::test(AdminHelpSettings::class);

    $languages = $page->get('data.languages');
    $languages['third'] = ['code' => 'hu', 'display' => 'Magyar', 'flag-icon' => 'hu'];

    $page->set('data.languages', $languages)
        ->set('data.fallback', '2')
        ->set('data.revisions_enabled', false)
        ->set('data.revisions_keep', '5')
        ->call('save')
        ->assertHasNoFormErrors();

    $stored = finCodexSettingsStored();

    expect($stored->fallback)->toBe(FallbackBehaviour::Hide)
        ->and($stored->revisions_keep)->toBe(5)
        ->and(is_int($stored->revisions_keep))->toBeTrue()
        ->and($stored->revisions_enabled)->toBeFalse()
        ->and(array_values($stored->languages))->toBe([
            ['code' => 'en', 'display' => 'English', 'flag-icon' => 'gb'],
            ['code' => 'de', 'display' => 'Deutsch', 'flag-icon' => 'de'],
            ['code' => 'hu', 'display' => 'Magyar', 'flag-icon' => 'hu'],
        ]);
});

it('offers the core enum\'s own labels for the fallback behaviour', function (): void {
    finCodexSettingsSeed();

    $this->usesPanel('admin', finCodexSettingsUser());

    $page = Livewire::test(AdminHelpSettings::class);
    $fallback = finCodexSettingsComponent($page, 'data.fallback');

    expect($fallback)->toBeInstanceOf(Select::class);

    /** @var Select $fallback */
    expect($fallback->getOptions())->toBe([
        FallbackBehaviour::ShowDefault->value => FallbackBehaviour::ShowDefault->label(),
        FallbackBehaviour::Hide->value => FallbackBehaviour::Hide->label(),
    ])
        ->and(array_keys($fallback->getOptions()))->toBe([1, 2]);
});

it('follows the languages repeater as it is edited, without a save', function (): void {
    finCodexSettingsSeed();

    $this->usesPanel('admin', finCodexSettingsUser());

    $page = Livewire::test(AdminHelpSettings::class);

    $options = fn (): array => finCodexSettingsComponent($page, 'data.default_locale')?->getOptions() ?? [];

    expect($options())->toBe(['en' => 'English', 'de' => 'Deutsch']);

    $page->set('data.languages', ['row' => ['code' => 'fr', 'display' => 'Français', 'flag-icon' => 'fr']]);

    expect($options())->toBe(['fr' => 'Français'])
        // Nothing was saved: the stored list is untouched.
        ->and(array_column(finCodexSettingsStored()->languages, 'code'))->toBe(['en', 'de']);
});

it('says on the revisions toggle that the editor tab disappears', function (): void {
    finCodexSettingsSeed();

    $this->usesPanel('admin', finCodexSettingsUser());

    Livewire::test(AdminHelpSettings::class)
        ->assertSee(__('fin-codex::fin-codex.settings.revisions.enabled_help'), escape: false);
});

it('is read back by the core on the next request', function (): void {
    finCodexSettingsSeed(['en'], 'en', FallbackBehaviour::ShowDefault, true, 7);

    $this->usesPanel('admin', finCodexSettingsUser());

    $page = Livewire::test(AdminHelpSettings::class);

    $languages = $page->get('data.languages');
    $languages['second'] = ['code' => 'de', 'display' => 'Deutsch', 'flag-icon' => 'de'];

    $page->set('data.languages', $languages)
        ->set('data.revisions_enabled', false)
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(CodexSettings::class);

    expect(app(RevisionManager::class)->enabled())->toBeFalse()
        ->and(array_column(TranslationTabs::languages()['languages'], 'code'))->toBe(['en', 'de'])
        ->and(TranslationTabs::languages()['default'])->toBe('en');
});
