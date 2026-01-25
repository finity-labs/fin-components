<?php

namespace FinityLabs\FinAvatar;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

use function Laravel\Prompts\select;

class FinAvatarServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fin-avatar';

    /** {@inheritDoc} */
    public function configurePackage(Package $package): void
    {
        /**
         * @var Package $package
         */
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasRoute('web')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function (InstallCommand $command) {
                        $panelId = select(
                            label: 'Which Panel would you like to install for?',
                            options: collect(Filament::getPanels())->keys(),
                            required: true
                        );

                        $panel = Filament::getPanel($panelId);

                        $panelPath = app_path(
                            (string) str($panel->getId())
                                ->studly()
                                ->append('PanelProvider')
                                ->prepend('Providers' . DIRECTORY_SEPARATOR . 'Filament' . DIRECTORY_SEPARATOR)
                                ->replace(['\\', '/'], DIRECTORY_SEPARATOR)
                                ->append('.php'),
                        );

                        if (! file_exists($panelPath)) {
                            $this->error('Panel not found: ' . $panelPath);
                            $this->info('Please read documentation for installation: https://github.com/finity-labs/fin-avatar');

                            return Command::FAILURE;
                        }

                        $content = file_get_contents($panelPath);

                        if (! str_contains($content, 'use FinityLabs\FinAvatar\AvatarProviders\UiAvatarsProvider;')) {
                            $lines = explode(PHP_EOL, $content);
                            
                            $found = false;
                            for($i = 0; $i < count($lines); $i++) {
                                if (str_starts_with($lines[$i], 'use ')) {
                                    $found = true;
                                } else if ($found) {
                                    array_splice($lines, $i, 0, 'use FinityLabs\FinAvatar\AvatarProviders\UiAvatarsProvider;');
                                    break;
                                }
                            }
                            
                            $found = false;
                            for($i = 0; $i < count($lines); $i++) {
                                if (str_contains($lines[$i], '->login()')) {
                                    $found = true;
                                    $command->info($lines[$i]);
                                } else if ($found) {
                                    array_splice($lines, $i, 0, '            ->defaultAvatarProvider(UiAvatarsProvider::class)');
                                    break;
                                }
                            }

                            $content = implode(PHP_EOL, $lines);

                            file_put_contents(
                                $panelPath,
                                $content);
                        }
                    })
                    ->askToStarRepoOnGitHub('finity-labs/fin-avatar');
            });
    }
}