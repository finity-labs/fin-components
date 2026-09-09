<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Console\Concerns;

use Illuminate\Support\Str;

/**
 * The host's panel providers, found by file name under app/Providers/Filament:
 * AdminPanelProvider.php is the "admin" panel. Panel id => file path.
 */
trait DiscoversPanelProviders
{
    /**
     * @return array<string, string>
     */
    protected function discoverPanelProviders(): array
    {
        $directory = app_path('Providers/Filament');

        if (! is_dir($directory)) {
            return [];
        }

        $files = glob($directory.'/*PanelProvider.php');

        if ($files === false) {
            return [];
        }

        $providers = [];

        foreach ($files as $file) {
            $panelId = (string) Str::of(basename($file, '.php'))
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
