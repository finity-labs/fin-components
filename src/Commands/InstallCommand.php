<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Commands;

use FinityLabs\FinSentinel\Commands\Concerns\CanRegisterPlugin;
use FinityLabs\FinSentinel\Commands\Concerns\DiscoversPanelProviders;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\multiselect;

#[AsCommand(name: 'fin-sentinel:install', description: 'Install the Fin Sentinel plugin')]
class InstallCommand extends Command
{
    use CanRegisterPlugin;
    use DiscoversPanelProviders;

    protected $signature = 'fin-sentinel:install {panels?*}';

    protected $description = 'Install the Fin Sentinel plugin.';

    public function handle(): int
    {
        $this->info('Installing Fin Sentinel plugin...');
        $this->newLine();

        // 1. Publish config
        $this->comment('Publishing configuration...');
        $this->callSilently('vendor:publish', [
            '--tag' => 'fin-sentinel-config',
        ]);
        $this->info('  Config published to config/fin-sentinel.php');

        // 2. Run settings migrations
        $this->ensureSettingsTableExists();

        $this->comment('Publishing migrations...');
        $this->callSilently('vendor:publish', [
            '--tag' => 'fin-sentinel-migrations',
        ]);
        $this->info('  Migrations published');

        $this->comment('Running migrations...');
        $this->call('migrate');
        $this->info('  Migrations complete');

        // 3. Panel selection + registration
        $this->registerInPanels();

        $this->newLine();
        $this->info('Fin Sentinel plugin installed successfully!');

        return self::SUCCESS;
    }

    protected function ensureSettingsTableExists(): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        $this->comment('Publishing spatie/laravel-settings migration...');
        $this->callSilently('vendor:publish', [
            '--provider' => 'Spatie\LaravelSettings\LaravelSettingsServiceProvider',
            '--tag' => 'migrations',
        ]);
        $this->info('  Settings migration published');

        $this->comment('Running settings migration...');
        $this->call('migrate');
        $this->info('  Settings table created');
    }

    protected function registerInPanels(): void
    {
        $panelProviders = $this->discoverPanelProviders();

        if (empty($panelProviders)) {
            $this->components->warn('No panel providers found in app/Providers/Filament/. Register FinSentinelPlugin::make() manually.');

            return;
        }

        $panelIds = array_keys($panelProviders);
        $requestedPanels = $this->argument('panels');

        if (! empty($requestedPanels)) {
            // Argument mode: use specified panel IDs directly
            $selectedPanels = $requestedPanels;
        } elseif ($this->input->isInteractive()) {
            // Interactive mode: show multiselect prompt
            $selectedPanels = multiselect(
                label: 'Which panels should Fin Sentinel be registered in?',
                options: array_combine($panelIds, $panelIds),
                default: $panelIds,
                required: true,
            );
        } else {
            // Non-interactive mode: default to all panels
            $selectedPanels = $panelIds;
        }

        $registered = [];

        foreach ($selectedPanels as $panelId) {
            if (! isset($panelProviders[$panelId])) {
                $this->components->warn("Panel provider not found for: {$panelId}");

                continue;
            }

            $this->comment("Registering FinSentinelPlugin in {$panelId} panel...");
            $this->registerPlugin($panelProviders[$panelId]);
            $registered[] = $panelId;
        }

        if (! empty($registered)) {
            $this->info('  Registered in panels: '.implode(', ', $registered));
        }
    }
}
