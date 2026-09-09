<?php

declare(strict_types=1);

namespace FinityLabs\LinSupport\Tests\Fixtures;

use FinityLabs\LinSupport\Console\Concerns\PromptsForLocales;
use Illuminate\Console\Command;

/**
 * The smallest command that uses the trait the way an installer does: ask,
 * turn the answer into settings rows, print them.
 */
final class ConfigureLocalesCommand extends Command
{
    use PromptsForLocales;

    protected $signature = 'fixture:locales {--locales= : Comma-separated locale codes}';

    protected $description = 'Fixture for PromptsForLocales.';

    public function handle(): int
    {
        $codes = $this->resolveLocales('Which languages should the fixture support?');

        foreach ($this->localeEntries($codes) as $entry) {
            $this->line(sprintf('%s|%s|%s', $entry['code'], $entry['display'], $entry['flag-icon']));
        }

        return self::SUCCESS;
    }
}
