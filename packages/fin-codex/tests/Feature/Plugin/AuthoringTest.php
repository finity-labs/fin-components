<?php

use Filament\Panel;
use FinityLabs\FinCodex\FinCodexPlugin;
use FinityLabs\FinCodex\Pages\HelpCoverage;
use FinityLabs\FinCodex\Pages\HelpSettings;
use FinityLabs\FinCodex\Resources\ArticleResource;
use FinityLabs\FinCodex\Tests\Fixtures\Pages\AdminHelpCoverage;
use FinityLabs\FinCodex\Tests\Fixtures\Pages\AdminHelpSettings;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\AdminHelpArticleResource;

/*
 * ->authoring(false): the reading half alone on a second panel. The subject is
 * what Panel::plugin() puts on the panel, and it calls the plugin's register()
 * there and then, so these panels are built outside the registry rather than
 * added to the fixture app as a fifth provider — a panel the registry knows
 * would change what every all-panels test counts.
 */

/** A panel of its own carrying one configured plugin instance. */
function codexPanel(FinCodexPlugin $plugin, string $id = 'authoring'): Panel
{
    return Panel::make()->id($id)->path($id)->plugin($plugin);
}

/**
 * The render hooks a panel holds, as name => number of closures.
 *
 * @return array<string, int>
 */
function renderHookCounts(Panel $panel): array
{
    /** @var array<string, array<string, array<Closure>>> $hooks */
    $hooks = (new ReflectionProperty(Panel::class, 'renderHooks'))->getValue($panel);

    return array_map(
        static fn (array $scopes): int => array_sum(array_map('count', $scopes)),
        $hooks,
    );
}

it('defaults to on and evaluates a closure', function (): void {
    $plugin = FinCodexPlugin::make();

    expect($plugin->hasAuthoring())->toBeTrue()
        ->and($plugin->authoring(false))->toBe($plugin)
        ->and($plugin->hasAuthoring())->toBeFalse()
        ->and($plugin->authoring(fn (): bool => false)->hasAuthoring())->toBeFalse()
        ->and($plugin->authoring()->hasAuthoring())->toBeTrue();
});

it('registers the editor and both pages on a panel that authors', function (): void {
    $panel = codexPanel(FinCodexPlugin::make());

    expect(array_values($panel->getResources()))->toContain(ArticleResource::class)
        ->and(array_values($panel->getPages()))
        ->toContain(HelpSettings::class)
        ->toContain(HelpCoverage::class);
});

it('registers no resource and neither page on a panel that only reads', function (): void {
    $panel = codexPanel(FinCodexPlugin::make()->authoring(false));

    expect(array_values($panel->getResources()))->toBe([])
        ->and(array_values($panel->getPages()))->toBe([]);
});

it('leaves the class overrides unregistered too', function (): void {
    $panel = codexPanel(
        FinCodexPlugin::make()
            ->articleResource(AdminHelpArticleResource::class)
            ->settingsPage(AdminHelpSettings::class)
            ->coveragePage(AdminHelpCoverage::class)
            ->authoring(fn (): bool => false),
    );

    expect(array_values($panel->getResources()))->toBe([])
        ->and(array_values($panel->getPages()))->toBe([]);
});

it('keeps every render hook, so the button and the drawer still mount', function (): void {
    $reader = renderHookCounts(codexPanel(FinCodexPlugin::make()->authoring(false), 'reader'));

    expect($reader)->toBe(renderHookCounts(codexPanel(FinCodexPlugin::make(), 'author')))
        ->and(array_sum($reader))->toBeGreaterThan(0);
});
