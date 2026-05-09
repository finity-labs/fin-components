<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Commands\Concerns;

trait ManagesThemeStyles
{
    private const THEME_SOURCE_LINE = "@source '../../../../vendor/finity-labs/fin-mail/resources/views/**/*';";

    protected function registerThemeStyles(string $panelId): void
    {
        $cssPath = $this->resolveThemeCssPath($panelId);

        if ($cssPath === null) {
            return;
        }

        $content = file_get_contents($cssPath);

        if ($content === false) {
            $this->components->warn("Could not read theme CSS file: {$cssPath}");

            return;
        }

        if (str_contains($content, 'finity-labs/fin-mail')) {
            $this->components->warn('FinMail styles are already registered in the theme CSS.');

            return;
        }

        $content = rtrim($content)."\n".self::THEME_SOURCE_LINE."\n";

        file_put_contents($cssPath, $content);

        $relativePath = str_replace(base_path().'/', '', $cssPath);
        $this->info("  FinMail styles registered in {$relativePath}");
    }

    protected function deregisterThemeStyles(string $panelId): void
    {
        $cssPath = $this->resolveThemeCssPath($panelId);

        if ($cssPath === null) {
            return;
        }

        $content = file_get_contents($cssPath);

        if ($content === false || ! str_contains($content, 'finity-labs/fin-mail')) {
            return;
        }

        $this->comment("Removing FinMail styles from {$panelId} panel theme...");

        // Remove the @source line (with optional surrounding blank lines)
        $content = preg_replace(
            '/\n?.*finity-labs\/fin-mail.*\n?/',
            "\n",
            $content,
        );

        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        file_put_contents($cssPath, rtrim($content)."\n");

        $relativePath = str_replace(base_path().'/', '', $cssPath);
        $this->info("  FinMail styles removed from {$relativePath}");
    }

    protected function resolveThemeCssPath(string $panelId): ?string
    {
        $cssPath = resource_path("css/filament/{$panelId}/theme.css");

        if (! file_exists($cssPath)) {
            return null;
        }

        return $cssPath;
    }
}
