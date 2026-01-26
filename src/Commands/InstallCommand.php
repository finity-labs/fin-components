<?php

declare(strict_types=1);

namespace FinityLabs\FinAvatar\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;

#[AsCommand(name: 'fin-avatar:install', description: 'Install and configure avatar provider')]
class InstallCommand extends Command
{
    /** @var string */
    protected $signature = 'fin-avatar:install {panel?}';

    public function handle(): int
    {
        $panelId = $this->argument('panel') ?: select(
            label: 'Which Panel would you like to install for?',
            options: collect(Filament::getPanels())->keys()->all(),
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

            return Command::FAILURE;
        }

        $content = file_get_contents($panelPath);

        if (! str_contains($content, 'use FinityLabs\FinAvatar\AvatarProviders\UiAvatarsProvider;')) {
            $lines = explode(PHP_EOL, $content);

            $found = false;
            for ($i = 0; $i < count($lines); $i++) {
                if (str_starts_with($lines[$i], 'use ')) {
                    $found = true;
                } elseif ($found) {
                    array_splice($lines, $i, 0, 'use FinityLabs\FinAvatar\AvatarProviders\UiAvatarsProvider;');

                    break;
                }
            }

            $found = false;
            for ($i = 0; $i < count($lines); $i++) {
                if (str_contains($lines[$i], '->login()')) {
                    $found = true;
                } elseif ($found) {
                    array_splice($lines, $i, 0, '            ->defaultAvatarProvider(UiAvatarsProvider::class)');

                    break;
                }
            }

            $content = implode(PHP_EOL, $lines);

            file_put_contents(
                $panelPath,
                $content
            );
        }

        return Command::SUCCESS;
    }
}
