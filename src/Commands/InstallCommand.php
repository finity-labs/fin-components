<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Commands;

use FinityLabs\FinMail\Commands\Concerns\CanRegisterPlugin;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;

class InstallCommand extends Command
{
    use CanRegisterPlugin;

    protected $signature = 'fin-mail:install
                            {--panel= : Panel ID to register the plugin in}
                            {--seed : Seed default email templates}
                            {--force : Overwrite existing config file}';

    protected $description = 'Install the FinMail plugin.';

    public function handle(): int
    {
        $this->info('Installing FinMail plugin...');
        $this->newLine();

        if ($this->confirm('Publish configuration file?', false)) {
            $this->comment('Publishing configuration...');
            $this->callSilently('vendor:publish', [
                '--tag' => 'fin-mail-config',
                '--force' => $this->option('force'),
            ]);
            $this->info('  Config published to config/fin-mail.php');
        }

        $this->comment('Publishing migrations...');
        $this->callSilently('vendor:publish', [
            '--tag' => 'fin-mail-migrations',
        ]);
        $this->callSilently('vendor:publish', [
            '--tag' => 'fin-mail-settings-migrations',
        ]);
        $this->info('  Migrations published (database + settings)');

        if ($this->confirm('Run migrations now?', true)) {
            $this->comment('Running migrations...');
            $this->call('migrate');
            $this->info('  Migrations complete');
        }

        if ($this->option('seed') || $this->confirm('Seed default email templates?', true)) {
            $this->comment('Seeding default templates...');
            $this->call('db:seed', [
                '--class' => \FinityLabs\FinMail\Database\Seeders\EmailTemplateSeeder::class,
            ]);
            $this->info('  Default templates seeded (5 templates + 1 theme)');
        }

        if ($this->confirm('Publish translation files for customization?', false)) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'fin-mail-translations',
            ]);
            $this->info('  Translations published to lang/vendor/fin-mail/');
        }

        if ($this->confirm('Publish email template views for customization?', false)) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'fin-mail-views',
            ]);
            $this->info('  Views published to resources/views/vendor/fin-mail/');
        }

        $this->registerInPanel();
        $this->configureSchedule();

        $this->newLine();
        $this->info('FinMail plugin installed successfully!');
        $this->newLine();

        $this->table(['Next Steps', 'Details'], [
            ['Configure settings', 'Visit the FinMail settings page in Filament admin'],
            ['Auth overrides', 'Set auth_emails options to true in config/fin-mail.php'],
        ]);

        return self::SUCCESS;
    }

    protected function registerInPanel(): void
    {
        $panelProviders = $this->discoverPanelProviders();

        if (empty($panelProviders)) {
            $this->components->warn('No panel providers found in app/Providers/Filament/. Register FinMailPlugin::make() manually.');

            return;
        }

        $panelId = $this->option('panel');

        if ($panelId === null) {
            $panelId = select(
                label: 'Which panel should FinMail be registered in?',
                options: array_keys($panelProviders),
                required: true,
            );
        }

        if (! isset($panelProviders[$panelId])) {
            $this->components->error("Panel provider not found for: {$panelId}");

            return;
        }

        $this->comment("Registering FinMailPlugin in {$panelId} panel...");
        $this->registerPlugin($panelProviders[$panelId]);
    }

    protected function configureSchedule(): void
    {
        if (! $this->confirm('Enable automatic cleanup of old sent emails?', true)) {
            return;
        }

        $frequency = select(
            label: 'How often should old sent emails be cleaned up?',
            options: [
                'daily' => 'Daily',
                'weekly' => 'Weekly',
                'monthly' => 'Monthly',
            ],
            default: 'daily',
        );

        $this->updateConfigValue('schedule.cleanup_enabled', true);
        $this->updateConfigValue('schedule.cleanup_frequency', $frequency);

        $this->info("  Cleanup scheduled ({$frequency}). Retention period is configurable in FinMail settings.");
    }

    protected function updateConfigValue(string $key, mixed $value): void
    {
        $configPath = config_path('fin-mail.php');

        if (! file_exists($configPath)) {
            return;
        }

        $content = file_get_contents($configPath);

        if ($content === false) {
            return;
        }

        // Parse the dotted key (e.g., "schedule.cleanup_enabled" → find 'cleanup_enabled' inside 'schedule' array)
        $parts = explode('.', $key);
        $configKey = array_pop($parts);

        $replacement = is_bool($value)
            ? ($value ? 'true' : 'false')
            : "'".$value."'";

        // Match the key => value pattern (handles bool, string, int values)
        $pattern = "/(['\"]".preg_quote($configKey, '/')."['\"]\\s*=>\\s*)([^,\\]\\n]+)/";

        $updated = preg_replace($pattern, '${1}'.$replacement, $content, 1);

        if ($updated !== null && $updated !== $content) {
            file_put_contents($configPath, $updated);
        }
    }

    /**
     * Scan app/Providers/Filament/ for panel provider files.
     *
     * @return array<string, string> Panel ID => file path
     */
    protected function discoverPanelProviders(): array
    {
        $directory = app_path('Providers/Filament');

        if (! is_dir($directory)) {
            return [];
        }

        $providers = [];
        $files = glob($directory.'/*PanelProvider.php');

        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            // AdminPanelProvider.php → admin
            $filename = basename($file, '.php');
            $panelId = (string) Str::of($filename)
                ->before('PanelProvider')
                ->snake()
                ->replace('_', '-');

            if ($panelId !== '') {
                $providers[$panelId] = $file;
            }
        }

        return $providers;
    }
}
