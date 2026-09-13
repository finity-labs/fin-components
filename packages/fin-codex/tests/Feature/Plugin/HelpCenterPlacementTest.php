<?php

use Filament\Support\Icons\Heroicon;
use FinityLabs\FinCodex\Enums\HelpCenterPlacement;
use FinityLabs\FinCodex\FinCodexPlugin;

/*
 * PLACE-01: where a panel's Help Center is reachable from.
 *
 * One option with four named states — the user menu, the navigation, both,
 * neither — plus the four options that decide what the navigation item looks
 * like. Under every one of them the page stays registered and its route stays
 * live, so the drawer footer, the field hints, the global search rows and a
 * bookmark all still reach it; the placement moves the two MENU ENTRIES and
 * nothing else.
 *
 * Helpers are file-local and finCodexPlacement*-prefixed. Pest "global"
 * helpers are only loaded for the files a run actually loads, so a
 * single-file run cannot see a sibling test file's functions (the convention
 * tests/Feature/HelpCenter/HelpCenterPageTest.php states for the same reason).
 */

/** @return array<string, mixed> The five placement options as the plugin reports them. */
function finCodexPlacementOptions(FinCodexPlugin $plugin): array
{
    return [
        'placement' => $plugin->getHelpCenterPlacement(),
        'group' => $plugin->getHelpCenterNavigationGroup(),
        'sort' => $plugin->getHelpCenterNavigationSort(),
        'label' => $plugin->getHelpCenterNavigationLabel(),
        'icon' => $plugin->getHelpCenterNavigationIcon(),
    ];
}

/*
 * -----------------------------------------------------------------------
 * The enum.
 * -----------------------------------------------------------------------
 */

it('names both surfaces from each placement', function (): void {
    expect(HelpCenterPlacement::UserMenu->inUserMenu())->toBeTrue()
        ->and(HelpCenterPlacement::UserMenu->inNavigation())->toBeFalse()
        ->and(HelpCenterPlacement::Navigation->inUserMenu())->toBeFalse()
        ->and(HelpCenterPlacement::Navigation->inNavigation())->toBeTrue()
        ->and(HelpCenterPlacement::Both->inUserMenu())->toBeTrue()
        ->and(HelpCenterPlacement::Both->inNavigation())->toBeTrue()
        ->and(HelpCenterPlacement::None->inUserMenu())->toBeFalse()
        ->and(HelpCenterPlacement::None->inNavigation())->toBeFalse();
});

it('backs the placement with a string, because it is UI-only and never stored', function (): void {
    expect(array_map(fn (HelpCenterPlacement $case): string => $case->value, HelpCenterPlacement::cases()))
        ->toBe(['user_menu', 'navigation', 'both', 'none']);
});

/*
 * -----------------------------------------------------------------------
 * The five options.
 * -----------------------------------------------------------------------
 */

it('ships the Help Center in the user menu, at the top level, at the bottom of the sidebar', function (): void {
    // Group null and sort 1000 on purpose: the Help Center is the reading
    // surface, not an admin screen, so it is not filed with the three
    // authoring screens and it lands below whatever the host arranged.
    expect(finCodexPlacementOptions(FinCodexPlugin::make()))->toBe([
        'placement' => HelpCenterPlacement::UserMenu,
        'group' => null,
        'sort' => 1000,
        'label' => 'Help center',
        'icon' => Heroicon::OutlinedBookOpen,
    ]);
});

it('returns itself from every placement setter and takes a literal', function (): void {
    $plugin = FinCodexPlugin::make();

    expect($plugin->helpCenterPlacement(HelpCenterPlacement::Both))->toBe($plugin)
        ->and($plugin->helpCenterNavigationGroup('Reading'))->toBe($plugin)
        ->and($plugin->helpCenterNavigationSort(10))->toBe($plugin)
        ->and($plugin->helpCenterNavigationLabel('Manual'))->toBe($plugin)
        ->and($plugin->helpCenterNavigationIcon(Heroicon::OutlinedAcademicCap))->toBe($plugin)
        ->and(finCodexPlacementOptions($plugin))->toBe([
            'placement' => HelpCenterPlacement::Both,
            'group' => 'Reading',
            'sort' => 10,
            'label' => 'Manual',
            'icon' => Heroicon::OutlinedAcademicCap,
        ]);
});

it('evaluates a closure in every placement getter', function (): void {
    $plugin = FinCodexPlugin::make()
        ->helpCenterPlacement(fn (): HelpCenterPlacement => HelpCenterPlacement::None)
        ->helpCenterNavigationGroup(fn (): string => 'Library')
        ->helpCenterNavigationSort(fn (): int => 20)
        ->helpCenterNavigationLabel(fn (): string => 'Handbook')
        ->helpCenterNavigationIcon(fn (): Heroicon => Heroicon::OutlinedBookmark);

    expect(finCodexPlacementOptions($plugin))->toBe([
        'placement' => HelpCenterPlacement::None,
        'group' => 'Library',
        'sort' => 20,
        'label' => 'Handbook',
        'icon' => Heroicon::OutlinedBookmark,
    ]);
});

it('degrades a closure that answers null to the documented default', function (): void {
    // Placement, label and icon have a default that is not null, so a closure
    // answering null must land back on it rather than fatal. Group and sort
    // default to a top-level item at sort 1000, and null there is a host
    // genuinely saying "no group" and "no sort".
    $plugin = FinCodexPlugin::make()
        ->helpCenterPlacement(fn () => null)
        ->helpCenterNavigationGroup(fn () => null)
        ->helpCenterNavigationSort(fn () => null)
        ->helpCenterNavigationLabel(fn () => null)
        ->helpCenterNavigationIcon(fn () => null);

    expect(finCodexPlacementOptions($plugin))->toBe([
        'placement' => HelpCenterPlacement::UserMenu,
        'group' => null,
        'sort' => null,
        'label' => 'Help center',
        'icon' => Heroicon::OutlinedBookOpen,
    ]);
});

it('leaves the three authoring screens on their own navigation options', function (): void {
    // The Help Center reads five options of its own; navigationGroup() and
    // navigationSort() keep meaning exactly what they meant before this
    // phase — the editor, Help settings and Help coverage.
    $plugin = FinCodexPlugin::make()->navigationGroup('Support')->navigationSort(5);

    expect($plugin->getHelpCenterNavigationGroup())->toBeNull()
        ->and($plugin->getHelpCenterNavigationSort())->toBe(1000)
        ->and($plugin->getNavigationGroup())->toBe('Support')
        ->and($plugin->getNavigationSort())->toBe(5);
});

it('translates the navigation label in all three locales', function (): void {
    $key = 'fin-codex::fin-codex.help_center.navigation';

    expect(__($key, [], 'en'))->toBe('Help center')
        ->and(__($key, [], 'de'))->toBe('Hilfecenter')
        ->and(__($key, [], 'hu'))->toBe('Súgóközpont');
});
