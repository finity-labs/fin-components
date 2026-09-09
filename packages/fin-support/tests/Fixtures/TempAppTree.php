<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures;

use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;

/**
 * The throwaway host application the installer traits are proven against:
 * real files at app_path() and config_path(), created here and deleted again.
 */
final class TempAppTree
{
    public const PANEL_PROVIDER = <<<'PHP'
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;

class {{class}} extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('{{id}}')
            ->path('{{id}}')
            ->login()
            ->middleware([
                'web',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
PHP;

    public const PANEL_PROVIDER_WITH_PLUGINS = <<<'PHP'
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;

class {{class}} extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('{{id}}')
            ->path('{{id}}')
            ->login()
            ->plugins([
                SomeOtherPlugin::make(),
            ])
            ->middleware([
                'web',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
PHP;

    public const SHIELD_CONFIG = <<<'PHP'
<?php

return [
    'resources' => [
        'subject' => 'model',
        'manage' => [
            \App\Filament\Resources\Users\UserResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
            ],
        ],
    ],

    'pages' => [
        'prefix' => 'view',
        'exclude' => [
            \Filament\Pages\Dashboard::class,
        ],
    ],
];
PHP;

    public static function providersDirectory(): string
    {
        return app_path('Providers/Filament');
    }

    public static function panelProviderPath(string $panelId = 'admin'): string
    {
        return self::providersDirectory().'/'.Str::studly($panelId).'PanelProvider.php';
    }

    public static function shieldConfigPath(): string
    {
        return config_path('filament-shield.php');
    }

    public static function writePanelProvider(string $panelId = 'admin', ?string $stub = null): string
    {
        if (! is_dir(self::providersDirectory())) {
            mkdir(self::providersDirectory(), 0777, true);
        }

        $path = self::panelProviderPath($panelId);

        file_put_contents($path, str_replace(
            ['{{class}}', '{{id}}'],
            [Str::studly($panelId).'PanelProvider', $panelId],
            $stub ?? self::PANEL_PROVIDER,
        ));

        return $path;
    }

    public static function writeShieldConfig(): string
    {
        file_put_contents(self::shieldConfigPath(), self::SHIELD_CONFIG);

        return self::shieldConfigPath();
    }

    /** php -l on the file: the edits must leave valid PHP behind. */
    public static function lints(string $path): bool
    {
        $process = new Process([PHP_BINARY, '-l', $path]);
        $process->run();

        return $process->isSuccessful();
    }

    public static function cleanup(): void
    {
        if (file_exists(self::shieldConfigPath())) {
            unlink(self::shieldConfigPath());
        }

        if (! is_dir(self::providersDirectory())) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::providersDirectory(), RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir(self::providersDirectory());
    }
}
