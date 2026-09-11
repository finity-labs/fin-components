# fin-support

Shared support code for Finity Labs Filament plugins. Builds on [lin-support](https://github.com/finity-labs/lin-support) and Filament 4 or 5; the pieces every `fin-*` package kept copying, kept once. Not a Filament plugin itself: there is nothing to register on a panel.

## Installation

```bash
composer require finity-labs/fin-support
```

## What is in it

### Page access with or without Shield

`FinityLabs\FinSupport\Pages\Concerns\HasPageShieldSupport` gates a Filament page three ways. With [Filament Shield](https://github.com/bezhanSalleh/filament-shield) installed it asks Shield for the page's permission, whatever naming the host configured, and follows it. Without Shield, a host may define a Gate ability named after the page class, `page_HelpSettings` for a `HelpSettings` page, and the trait follows that. With neither, `canAccessFallback()` answers, which is open unless the page overrides it.

```php
use Filament\Pages\Page;
use FinityLabs\FinSupport\Pages\Concerns\HasPageShieldSupport;

class ManageSettings extends Page
{
    use HasPageShieldSupport;

    // Optional: a plugin option as the last word.
    protected static function canAccessFallback(): bool
    {
        return MyPlugin::get()->userCanAccess();
    }
}
```

Shield is never a hard dependency: its classes are reached behind `class_exists()`.

### Policy registration per panel

`FinityLabs\FinSupport\Auth\PolicyRegistrar` maps the policies a host writes at `{namespace}\{Basename}` onto a package's models, registering a shipped fallback when the host has none:

```php
use FinityLabs\FinSupport\Auth\PolicyRegistrar;

PolicyRegistrar::register($namespace, [
    Article::class => 'ArticlePolicy',
], [
    Article::class => ShippedArticlePolicy::class,
]);
```

Call it twice: from your service provider's boot with `PolicyRegistrar::namespaceOf('my-plugin')`, which reads the default panel's `policyNamespace()` option or falls back to `App\Policies`, and from your plugin's `boot()` with that panel's own option. That second call is what makes the option work per panel: Filament boots the current panel after the providers, so the provider alone only ever sees the default panel.

### Installer helpers

Three traits for install and uninstall commands:

- `Console\Concerns\DiscoversPanelProviders` finds the host's panel providers under `app/Providers/Filament` by file name: `AdminPanelProvider.php` is the `admin` panel.
- `Console\Concerns\EditsPanelProviders` adds `MyPlugin::make(),` to a provider's `->plugins([...])` block, creating the block when there is none, with the matching `use` line, and removes it again with every chained option. Both refuse to repeat themselves.
- `Console\Concerns\EditsShieldConfig` writes a package's resources and abilities into `config/filament-shield.php`'s `resources.manage` array, once, and takes them out again.

```php
use FinityLabs\FinSupport\Console\Concerns\DiscoversPanelProviders;
use FinityLabs\FinSupport\Console\Concerns\EditsPanelProviders;
use FinityLabs\FinSupport\Console\Concerns\EditsShieldConfig;

class InstallCommand extends Command
{
    use DiscoversPanelProviders;
    use EditsPanelProviders;
    use EditsShieldConfig;

    public function handle(): int
    {
        $providers = $this->discoverPanelProviders();          // ['admin' => '/app/Providers/Filament/AdminPanelProvider.php']
        $this->registerPlugin($providers['admin'], MyPlugin::class);

        if ($this->hasShieldConfig()) {
            $this->registerShieldResources([MyResource::class => ['viewAny', 'view']], 'Vendor\\MyPackage');
        }

        return self::SUCCESS;
    }
}
```

### The panel user

`FinityLabs\FinSupport\Panel\Concerns\ResolvesPanelUser::panelUserId()` is `Filament::auth()->id()` narrowed to `int|string|null`, for packages that store the author of a row. An auto-increment host hands back the int, a host whose user model uses `HasUuids` or `HasUlids` the string key, and a guard with nobody signed in null; anything else becomes null.

`FinityLabs\FinSupport\Panel\PanelUser::id()` is the same answer as a static call, for the static closures of a schema or an action where there is no `$this` to take the trait method from.

### UUID or ULID user models

Until 0.1.1 `panelUserId()` returned `?int` and answered null for a string key, so a package storing the author recorded nobody on a `HasUuids` or `HasUlids` host. It now returns the key as it is. If your own code narrows the result, widen the type it is assigned to:

```php
-protected function getAuthorId(): ?int
+protected function getAuthorId(): int|string|null
 {
     return $this->panelUserId();
 }
```

There are no columns to migrate: fin-support stores nothing of its own.

## Testing

```bash
composer test
composer analyse
composer format
```

## License

MIT. See [LICENSE](LICENSE).
