# lin-support

Shared support code for Finity Labs Laravel packages. Plain Laravel, no Filament: the pieces that `lin-*` and `fin-*` packages kept copying from one another, kept once.

## Installation

```bash
composer require finity-labs/lin-support
```

Nothing to publish and no service provider: the package is classes and one console trait.

## What is in it

### The locale map

`FinityLabs\LinSupport\Locale\LocaleMap` knows sixty locales by code, each with its native display name and the flag-icon code that stands for it.

```php
use FinityLabs\LinSupport\Locale\LocaleMap;

LocaleMap::entry('de');            // ['code' => 'de', 'display' => 'Deutsch', 'flag-icon' => 'de']
LocaleMap::entries(['en', 'de']);  // the same rows for several codes, blanks and duplicates dropped
LocaleMap::label('hu');            // 'Magyar (hu)'
LocaleMap::has('xx');              // false
LocaleMap::entry('xx');            // derived: intl's display name when loaded, 'XX' otherwise
```

The row shape — `code`, `display`, `flag-icon` — is the one the packages' language settings store, so an entry goes straight into a settings class.

### Installed locales

`FinityLabs\LinSupport\Locale\InstalledLocales::detect()` lists the locales an application translates: one per directory under its lang path, `vendor` excluded, sorted, or the application locale alone when there is none.

### The locale prompt for install commands

`FinityLabs\LinSupport\Console\Concerns\PromptsForLocales` gives an install command its "which languages?" step. Declare a `--locales=` option and ask:

```php
use FinityLabs\LinSupport\Console\Concerns\PromptsForLocales;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    use PromptsForLocales;

    protected $signature = 'my-package:install {--locales= : Comma-separated locale codes to activate (e.g. en,hu,de)}';

    public function handle(): int
    {
        $codes = $this->resolveLocales('Which languages should My Package support?');

        $settings->languages = $this->localeEntries($codes);
        // ...
    }
}
```

With `--locales=en,de` the command answers without a prompt and warns about codes it does not know. Without it, the command offers the installed locales pre-selected, plus an "Other" entry that opens a free-text field for more codes. Every method on the trait is protected, so a command can override the detection or a message.

## Testing

```bash
composer test
composer analyse
composer format
```

## License

MIT. See [LICENSE](LICENSE).
