<?php

use Filament\Facades\Filament;
use Filament\Support\View\ViewManager;
use FinityLabs\FinCodex\FinCodexPlugin;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Rendering\ArticlePath;

/*
 * PLACE-02: inside a panel, every help link fin-codex or the core renderer
 * writes points at THAT panel's own Help Center.
 *
 * One derived write does it. FinCodexPlugin::boot() puts the panel's own
 * help-center path into lin-codex.routes.help_center, and the core's
 * ArticlePath reads that key at call time, so the drawer footer, the field
 * hints, the global-search rows and every article-to-article link follow with
 * no setter and no core change. The value is derived, never accumulated, so
 * repeated boots are harmless and a later write may overwrite an earlier one.
 *
 * The SPA exception is built FROM that value and therefore has to be applied
 * after it, which is why bootSpaExceptions() is called from inside the write
 * rather than beside it in boot(). A blank value would make the pattern match
 * every application URL and switch SPA navigation off panel-wide — the state
 * a fin-codex install leaves the published config in — so the guard against
 * that is pinned here too.
 *
 * Helpers are finCodexHelpCenterPrefix*-prefixed: Pest loads "global" helpers
 * per file, and tests/Feature/Forms/SpaModeTest.php carries its own reflection
 * reader under a different name, so a full run must not see two of either.
 */

function finCodexHelpCenterPrefixUser(string $email = 'prefix@example.com'): User
{
    return User::create(['name' => 'Reader', 'email' => $email]);
}

/** The plugin instance a fixture panel carries. */
function finCodexHelpCenterPrefixPlugin(string $panelId = 'admin'): FinCodexPlugin
{
    $plugin = Filament::getPanel($panelId)->getPlugin(FinCodexPlugin::ID);

    if (! $plugin instanceof FinCodexPlugin) {
        test()->fail("The {$panelId} panel carries no fin-codex plugin.");
    }

    return $plugin;
}

/**
 * The SPA URL exception list, which the manager keeps private and exposes
 * only through hasSpaMode(). Read directly so a row can count entries rather
 * than only ask whether one matches.
 *
 * @return list<string>
 */
function finCodexHelpCenterPrefixExceptions(ViewManager $view): array
{
    $property = new ReflectionProperty($view, 'spaModeUrlExceptions');

    return array_values($property->getValue($view));
}

it("writes the booted panel's own help center into the core's prefix", function (string $panel, string $prefix): void {
    $this->usesPanel($panel, finCodexHelpCenterPrefixUser());

    expect(config('lin-codex.routes.help_center'))->toBe($prefix);
})->with([
    'admin' => ['admin', '/admin/help'],
    'staff' => ['staff', '/staff/help'],
]);

it('steers the core link builders, which read the key at call time', function (): void {
    $this->usesPanel('admin', finCodexHelpCenterPrefixUser());

    expect(ArticlePath::href('users/roles', 'x'))->toBe('/admin/help/users/roles#x')
        ->and(ArticlePath::href('users'))->toBe('/admin/help/users')
        ->and(ArticlePath::helpCenterHref())->toBe('http://localhost/admin/help');
});

it('answers an absolute help-center URL for a named panel and for the current one', function (): void {
    $this->usesPanel('staff', finCodexHelpCenterPrefixUser());
    $plugin = finCodexHelpCenterPrefixPlugin('staff');

    expect($plugin->helpCenterUrl('admin'))->toBe('http://localhost/admin/help')
        ->and($plugin->helpCenterUrl())->toBe('http://localhost/staff/help');
});

it('answers null rather than throwing when no URL can be built', function (string $panelId): void {
    expect(finCodexHelpCenterPrefixPlugin()->helpCenterUrl($panelId))->toBeNull();
})->with([
    'a panel without the plugin, so without the route' => ['plain'],
    'a panel that does not exist' => ['no-such-panel'],
]);

it('keeps SPA navigation on everywhere but the help center when the published prefix is null', function (): void {
    config(['lin-codex.routes.help_center' => null]);
    Filament::getPanel('admin')->spa();
    $this->usesPanel('admin', finCodexHelpCenterPrefixUser());

    $view = app(ViewManager::class);

    expect($view->hasSpaMode('/admin/users'))->toBeTrue()
        ->and($view->hasSpaMode('/admin/help/x'))->toBeFalse()
        ->and(finCodexHelpCenterPrefixExceptions($view))->toBe(['/admin/help/*']);
});

it('adds no exception at all while the prefix is blank', function (): void {
    Filament::getPanel('admin')->spa();
    $panel = $this->usesPanel('admin', finCodexHelpCenterPrefixUser());

    $view = app(ViewManager::class);
    $before = finCodexHelpCenterPrefixExceptions($view);
    $plugin = finCodexHelpCenterPrefixPlugin();

    config(['lin-codex.routes.help_center' => null]);
    (new ReflectionMethod($plugin, 'bootSpaExceptions'))->invoke($plugin, $panel);

    expect(finCodexHelpCenterPrefixExceptions($view))->toBe($before)
        ->and($view->hasSpaMode('/anything/at/all'))->toBeTrue();
});

it('writes the same value and one exception across repeated boots', function (): void {
    Filament::getPanel('admin')->spa();
    $this->usesPanel('admin', finCodexHelpCenterPrefixUser());

    $view = app(ViewManager::class);
    $before = finCodexHelpCenterPrefixExceptions($view);

    Filament::getPanel('admin')->boot();
    Filament::getPanel('admin')->boot();

    expect(config('lin-codex.routes.help_center'))->toBe('/admin/help')
        ->and(array_count_values(finCodexHelpCenterPrefixExceptions($view))['/admin/help/*'] ?? 0)->toBe(1)
        ->and(finCodexHelpCenterPrefixExceptions($view))->toBe($before);
});
