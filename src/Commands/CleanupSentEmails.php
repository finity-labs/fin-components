<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Commands;

use FinityLabs\FinMail\Models\SentEmail;
use FinityLabs\FinMail\Settings\LoggingSettings;
use Illuminate\Console\Command;

class CleanupSentEmails extends Command
{
    protected $signature = 'fin-mail:cleanup
                            {--days= : Number of days to retain (overrides config)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Delete old sent email records based on retention policy.';

    public function handle(): int
    {
        $days = $this->option('days')
            ?? app(LoggingSettings::class)->retention_days;

        if (! $days) {
            $this->info('No retention period configured. Set logging.retention_days in settings or use --days.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays((int) $days);
        $query = SentEmail::where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($count === 0) {
            $this->info('No sent emails older than '.$days.' days.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[Dry run] Would delete {$count} sent email records older than {$days} days.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Deleted {$deleted} sent email records older than {$days} days.");

        return self::SUCCESS;
    }
}
