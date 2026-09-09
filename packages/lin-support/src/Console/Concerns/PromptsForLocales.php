<?php

declare(strict_types=1);

namespace FinityLabs\LinSupport\Console\Concerns;

use FinityLabs\LinSupport\Locale\InstalledLocales;
use FinityLabs\LinSupport\Locale\LocaleMap;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

/**
 * The "which languages?" step of an install command.
 *
 * The command declares a `--locales=` option (comma-separated codes) and calls
 * resolveLocales() with the question to ask. The option answers without a
 * prompt, unknown codes dropped with a warning; without it, the command
 * offers the application's installed locales, pre-selected, plus an "other"
 * entry that opens a free-text field for more codes. The result is a clean
 * list of codes; localeEntries() turns it into settings rows.
 *
 * Every method is protected and overridable, so a command can narrow the
 * detection or change a message without giving up the rest.
 */
trait PromptsForLocales
{
    /**
     * The codes the admin chose, from the option or the prompt.
     *
     * @return list<string>
     */
    protected function resolveLocales(string $label): array
    {
        $option = $this->option('locales');

        if (is_string($option) && trim($option) !== '') {
            return $this->parseLocalesOption($option, $label);
        }

        return $this->promptLocales($label);
    }

    /**
     * The settings rows for the chosen codes.
     *
     * @param  list<string>  $codes
     *
     * @return list<array{code: string, display: string, 'flag-icon': string}>
     */
    protected function localeEntries(array $codes): array
    {
        return LocaleMap::entries($codes);
    }

    /**
     * @return list<string>
     */
    protected function parseLocalesOption(string $value, string $label): array
    {
        $codes = array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $code): bool => $code !== ''));
        $unknown = array_values(array_filter($codes, static fn (string $code): bool => ! LocaleMap::has($code)));

        if ($unknown !== []) {
            $this->components->warn('Unknown locale codes ignored: '.implode(', ', $unknown));
            $codes = array_values(array_filter($codes, static fn (string $code): bool => LocaleMap::has($code)));
        }

        if ($codes === []) {
            $this->components->warn('No valid locales provided. Falling back to interactive selection.');

            return $this->promptLocales($label);
        }

        $this->comment('Locales from --locales option: '.implode(', ', $codes));

        return array_values(array_unique($codes));
    }

    /**
     * @return list<string>
     */
    protected function promptLocales(string $label): array
    {
        $detected = $this->detectLocales();
        $options = [];

        foreach ($detected as $code) {
            $options[$code] = LocaleMap::label($code);
        }

        $options['other'] = 'Other (enter locale codes manually)';

        $this->comment('Detected locales: '.implode(', ', $detected));

        /** @var list<string> $selected */
        $selected = array_values(multiselect(
            label: $label,
            options: $options,
            default: $detected,
            required: true,
        ));

        if (! in_array('other', $selected, true)) {
            return $selected;
        }

        $selected = array_values(array_filter($selected, static fn (string $code): bool => $code !== 'other'));

        $extra = text(
            label: 'Enter additional locale codes (comma-separated)',
            placeholder: 'e.g. ja,ko,zh_CN',
            hint: 'Available: '.implode(', ', LocaleMap::codes()),
            required: true,
        );

        return array_values(array_unique([...$selected, ...$this->parseLocalesOption($extra, $label)]));
    }

    /**
     * @return list<string>
     */
    protected function detectLocales(): array
    {
        return InstalledLocales::detect();
    }
}
