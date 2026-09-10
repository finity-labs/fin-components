<?php

use FinityLabs\FinCodex\Commands\InstallCommand;
use FinityLabs\FinCodex\Tests\Fixtures\FakeAiClient;
use FinityLabs\FinCodex\Tests\Fixtures\TempAppTree;
use FinityLabs\LinCodex\Settings\CodexAiSettings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/*
 * AISET-04, the AI step in fin-codex:install.
 *
 * The rows split into two shapes on purpose.
 *
 * The prompting rows run `--ai-only` through $this->artisan(), because
 * PendingCommand replaces the output with a Mockery double that throws on any
 * question the test did not expect: `--ai-only` is what keeps the panel, the
 * languages and the Shield prompts out of the run, so only the three AI
 * questions are left.
 *
 * Every other row runs unmocked with --no-interaction and reads
 * Artisan::output() back, the shape InstallCommandTest already uses, because
 * what those rows prove is precisely that a non-interactive run never reaches
 * a prompt.
 *
 * The seam is the fake AiClient, never the SDK: finCodexFakeAi() answers
 * installed(), providers(), tierModels() and testConnection(), and
 * finCodexAiMockedInstall() replaces the one method that would shell out to
 * composer, so these rows run on every CI row. The rows that need the step to
 * get past its version gate skip on PHP 8.2 and Laravel 11, where the gate
 * warns instead - and the gate row itself skips in the opposite case, so the
 * matrix covers both sides.
 */

afterEach(function () {
    TempAppTree::cleanup();
});

/**
 * Run a fin-codex command non-interactively and hand back its exit code and
 * its real output. Named apart from InstallCommandTest's twin for the reason
 * UninstallCommandTest gives: Pest helpers are global functions, so a shared
 * name is a fatal redeclaration in a full run and a missing function in a
 * single-file one.
 *
 * @param  array<string, mixed>  $parameters
 *
 * @return array{0: int, 1: string}
 */
function finCodexAiRunCommand(string $command, array $parameters = []): array
{
    test()->withoutMockingConsoleOutput();

    $exitCode = test()->artisan($command, [...$parameters, '--no-interaction' => true]);

    return [$exitCode, Artisan::output()];
}

/** The floor the AI step itself checks: PHP 8.3+ and Laravel 12+. */
function finCodexAiInstallGateMet(): bool
{
    return PHP_VERSION_ID >= 80300 && (int) explode('.', app()->version())[0] >= 12;
}

function finCodexAiSkipUnlessGateMet(): void
{
    if (! finCodexAiInstallGateMet()) {
        test()->markTestSkipped('The AI step needs PHP 8.3+ and Laravel 12+; this row runs on the other CI rows.');
    }
}

/**
 * Bind an InstallCommand whose composer call is a printed line rather than a
 * process. Artisan resolves the command class through the container when it
 * starts, so the bind has to happen before the artisan() call.
 */
function finCodexAiMockedInstall(bool $composerSucceeds): void
{
    app()->instance(InstallCommand::class, new class($composerSucceeds) extends InstallCommand
    {
        public function __construct(private bool $composerSucceeds)
        {
            parent::__construct();
        }

        protected function runComposerRequire(string $package): bool
        {
            $this->components->info("(mocked) composer require {$package}");

            return $this->composerSucceeds;
        }
    });
}

/**
 * The stored AI settings, read back from the rows rather than from the
 * container: spatie binds every settings class as a singleton, so without the
 * forget this would hand back the very object the command just filled.
 */
function finCodexAiStored(): CodexAiSettings
{
    app()->forgetInstance(CodexAiSettings::class);

    return app(CodexAiSettings::class);
}

it('never asks about AI in a non-interactive run without the flag', function () {
    TempAppTree::writePanelProvider('admin');

    [$exitCode, $output] = finCodexAiRunCommand('fin-codex:install', [
        '--panel' => 'admin',
        '--skip-starter-articles' => true,
    ]);

    expect($exitCode)->toBe(0)
        ->and($output)->not->toContain('Set up AI translation')
        ->and($output)->not->toContain('--ai-only')
        ->and(finCodexAiStored()->enabled)->toBeFalse()
        ->and(finCodexAiRows())->toBe(6);
});

it('warns and skips below PHP 8.3 or Laravel 12', function () {
    if (finCodexAiInstallGateMet()) {
        test()->markTestSkipped('This row proves the warning, so it only runs where the version gate fails.');
    }

    [$exitCode, $output] = finCodexAiRunCommand('fin-codex:install', ['--ai-only' => true]);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('PHP 8.3+')
        ->and($output)->toContain('fin-codex:install --ai-only')
        ->and(finCodexAiStored()->enabled)->toBeFalse();
});

it('offers the SDK and stops after installing it', function () {
    finCodexAiSkipUnlessGateMet();

    finCodexFakeAi(new FakeAiClient(installed: false));
    finCodexAiMockedInstall(composerSucceeds: true);

    [$exitCode, $output] = finCodexAiRunCommand('fin-codex:install', ['--ai-only' => true]);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('(mocked) composer require laravel/ai:^0.11')
        ->and($output)->toContain('SDK installed')
        ->and($output)->toContain('fin-codex:install --ai-only')
        ->and($output)->not->toContain('Registering FinCodexPlugin')
        ->and(finCodexAiStored()->enabled)->toBeFalse();
});

it('prints the composer error and the manual line when the require fails', function () {
    finCodexAiSkipUnlessGateMet();

    finCodexFakeAi(new FakeAiClient(installed: false));
    finCodexAiMockedInstall(composerSucceeds: false);

    [$exitCode, $output] = finCodexAiRunCommand('fin-codex:install', ['--ai-only' => true]);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('composer require laravel/ai:^0.11')
        ->and($output)->not->toContain('SDK installed')
        ->and(finCodexAiStored()->enabled)->toBeFalse();
});

it('asks for an interactive run when the SDK is present but the input is not', function () {
    finCodexAiSkipUnlessGateMet();

    $fake = finCodexFakeAi();

    [$exitCode, $output] = finCodexAiRunCommand('fin-codex:install', ['--ai-only' => true]);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('fin-codex:install --ai-only')
        ->and($fake->connectionTests)->toBe([])
        ->and(finCodexAiStored()->enabled)->toBeFalse();
});

it('asks for an interactive run when --ai rides along with a full non-interactive install', function () {
    finCodexAiSkipUnlessGateMet();

    $fake = finCodexFakeAi();
    TempAppTree::writePanelProvider('admin');

    [$exitCode, $output] = finCodexAiRunCommand('fin-codex:install', [
        '--ai' => true,
        '--panel' => 'admin',
        '--skip-starter-articles' => true,
    ]);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Registering FinCodexPlugin')
        ->and($output)->toContain('fin-codex:install --ai-only')
        ->and($fake->connectionTests)->toBe([])
        ->and(finCodexAiStored()->enabled)->toBeFalse();
});

it('points at codex:install when the core tables are missing', function () {
    finCodexAiSkipUnlessGateMet();

    $fake = finCodexFakeAi();

    Schema::drop((string) config('lin-codex.table_names.articles', 'codex_articles'));

    [$exitCode, $output] = finCodexAiRunCommand('fin-codex:install', ['--ai-only' => true]);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('codex:install')
        ->and($fake->connectionTests)->toBe([]);
});
