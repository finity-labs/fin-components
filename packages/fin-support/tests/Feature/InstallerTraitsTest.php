<?php

declare(strict_types=1);

use FinityLabs\FinSupport\Tests\Fixtures\InstallerCommand;
use FinityLabs\FinSupport\Tests\Fixtures\TempAppTree;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    Artisan::registerCommand(app(InstallerCommand::class));
});

afterEach(function (): void {
    TempAppTree::cleanup();
});

/** @return array{0: int, 1: string} */
function finSupportInstaller(string $step, string $panel = 'admin'): array
{
    test()->withoutMockingConsoleOutput();

    $exitCode = test()->artisan('fixture:installer', ['step' => $step, 'panel' => $panel, '--no-interaction' => true]);

    return [$exitCode, Artisan::output()];
}

it('discovers panel providers by file name', function (): void {
    TempAppTree::writePanelProvider('admin');
    TempAppTree::writePanelProvider('staff-portal');

    [$exitCode, $output] = finSupportInstaller('discover');

    expect($exitCode)->toBe(0)->and(trim($output))->toBe('admin,staff-portal');
});

it('registers the plugin, creating the plugins block, and leaves valid PHP', function (): void {
    $path = TempAppTree::writePanelProvider('admin');

    [$exitCode] = finSupportInstaller('register');
    $content = (string) file_get_contents($path);

    expect($exitCode)->toBe(0)
        ->and($content)->toContain('FixturePlugin::make()')
        ->toContain('use FinityLabs\FinSupport\Tests\Fixtures\FixturePlugin;')
        ->toContain('->plugins([')
        ->and(TempAppTree::lints($path))->toBeTrue();
});

it('appends to an existing plugins block instead of creating a second one', function (): void {
    $path = TempAppTree::writePanelProvider('admin', TempAppTree::PANEL_PROVIDER_WITH_PLUGINS);

    finSupportInstaller('register');
    $content = (string) file_get_contents($path);

    expect(substr_count($content, '->plugins(['))->toBe(1)
        ->and($content)->toContain('SomeOtherPlugin::make()')
        ->toContain('FixturePlugin::make()')
        ->and(TempAppTree::lints($path))->toBeTrue();
});

it('refuses a second registration', function (): void {
    $path = TempAppTree::writePanelProvider('admin');

    finSupportInstaller('register');
    [$exitCode, $output] = finSupportInstaller('register');

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('already registered')
        ->and(substr_count((string) file_get_contents($path), 'FixturePlugin::make()'))->toBe(1);
});

it('deregisters as a byte-for-byte round trip, and reports a plugin that is not there', function (): void {
    $path = TempAppTree::writePanelProvider('admin');
    $before = (string) file_get_contents($path);

    finSupportInstaller('register');
    [$exitCode] = finSupportInstaller('deregister');

    expect($exitCode)->toBe(0)
        ->and((string) file_get_contents($path))->toBe($before);

    [$exitCode, $output] = finSupportInstaller('deregister');

    expect($exitCode)->toBe(1)->and($output)->toContain('not registered');
});

it('removes a multi-line registration with chained options', function (): void {
    $path = TempAppTree::writePanelProvider('admin', TempAppTree::PANEL_PROVIDER_WITH_PLUGINS);
    $content = str_replace(
        "                SomeOtherPlugin::make(),\n",
        "                SomeOtherPlugin::make(),\n                FixturePlugin::make()\n                    ->policyNamespace('App\\\\Policies')\n                    ->option(fn () => 'x, (y)'),\n",
        (string) file_get_contents($path),
    );
    file_put_contents($path, "<?php\nuse FinityLabs\\FinSupport\\Tests\\Fixtures\\FixturePlugin;\n".substr($content, 6));

    [$exitCode] = finSupportInstaller('deregister');
    $after = (string) file_get_contents($path);

    expect($exitCode)->toBe(0)
        ->and($after)->not->toContain('FixturePlugin')
        ->toContain('SomeOtherPlugin::make()')
        ->and(TempAppTree::lints($path))->toBeTrue();
});

it('writes resources into the Shield manage array once, and takes them out again', function (): void {
    $shield = TempAppTree::writeShieldConfig();

    [$exitCode] = finSupportInstaller('shield-register');
    $content = (string) file_get_contents($shield);

    expect($exitCode)->toBe(0)
        ->and($content)->toContain("\\FinityLabs\\FinSupport\\Tests\\Fixtures\\ThingResource::class => [\n                'viewAny',\n                'view',\n                'restore',\n            ],")
        ->toContain('UserResource::class')
        ->and(TempAppTree::lints($shield))->toBeTrue();

    [$second, $output] = finSupportInstaller('shield-register');

    expect($second)->toBe(1)
        ->and($output)->toContain('already registered')
        ->and(substr_count((string) file_get_contents($shield), 'ThingResource::class'))->toBe(1);

    [$removed] = finSupportInstaller('shield-unregister');
    $after = (string) file_get_contents($shield);

    expect($removed)->toBe(0)
        ->and($after)->not->toContain('ThingResource')
        ->toContain('UserResource::class')
        ->and(TempAppTree::lints($shield))->toBeTrue();
});
