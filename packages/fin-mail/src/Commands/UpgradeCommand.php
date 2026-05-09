<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Commands;

use FinityLabs\FinMail\Database\Seeders\EmailTemplateSeeder;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpgradeCommand extends Command
{
    protected $signature = 'fin-mail:upgrade
                            {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Upgrade FinMail data to match the latest version.';

    protected int $updated = 0;

    protected int $skipped = 0;

    public function handle(): int
    {
        $this->info('FinMail Upgrade');
        $this->newLine();

        $this->upgradeLockedTemplates();

        $this->newLine();

        if ($this->updated === 0 && $this->skipped === 0) {
            $this->info('Everything is up to date.');
        } else {
            $this->info("Done. Updated: {$this->updated}, Skipped: {$this->skipped}");
        }

        return self::SUCCESS;
    }

    protected function upgradeLockedTemplates(): void
    {
        $this->comment('Checking locked templates...');

        $seeder = new EmailTemplateSeeder;
        $definitions = $this->getSeederDefinitions($seeder);

        foreach ($definitions as $definition) {
            if (! ($definition['is_locked'] ?? false)) {
                continue;
            }

            $template = EmailTemplate::where('key', $definition['key'])->first();

            if (! $template) {
                $this->skipped++;
                $this->line("  <fg=yellow>⊘</> {$definition['key']} — not found in database, skipping");

                continue;
            }

            $changed = false;

            foreach ($definition['body'] as $locale => $expectedBody) {
                $currentBody = $template->getTranslation('body', $locale, false);

                if ($currentBody === $expectedBody) {
                    continue;
                }

                if ($currentBody === null || $currentBody === '') {
                    continue;
                }

                $changed = true;

                if (! $this->option('dry-run')) {
                    $template->setTranslation('body', $locale, $expectedBody);
                }
            }

            if ($changed) {
                if (! $this->option('dry-run')) {
                    $template->saveQuietly();
                }

                $this->updated++;
                $label = $this->option('dry-run') ? 'would update' : 'updated';
                $this->line("  <fg=green>✓</> {$definition['key']} — {$label}");
            } else {
                $this->skipped++;
                $this->line("  <fg=gray>–</> {$definition['key']} — already up to date");
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getSeederDefinitions(EmailTemplateSeeder $seeder): array
    {
        $reflection = new \ReflectionMethod($seeder, 'getTemplateDefinitions');

        return $reflection->invoke($seeder);
    }
}
