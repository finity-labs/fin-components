<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Console\Concerns;

/**
 * Writing a package's resources into config/filament-shield.php's
 * resources.manage array, and taking them out again. The insertion point is
 * found by counting brackets from the array's opening one, so a host's own
 * entries are left exactly as they were; a repeated install is refused when
 * the marker (the package's namespace prefix) is already in the file.
 *
 * Pages need nothing here: Shield 4 discovers pages from the panel and only
 * reads pages.exclude from config.
 */
trait EditsShieldConfig
{
    protected function shieldConfigPath(): string
    {
        return config_path('filament-shield.php');
    }

    protected function hasShieldConfig(): bool
    {
        return file_exists($this->shieldConfigPath());
    }

    /**
     * @param  array<class-string, list<string>>  $resources  resource class => its abilities
     * @param  string  $marker  a namespace prefix that identifies the package's entries, e.g. 'FinityLabs\\FinCodex'
     */
    protected function registerShieldResources(array $resources, string $marker): bool
    {
        $content = file_get_contents($this->shieldConfigPath());

        if ($content === false) {
            $this->components->warn('Could not read the Shield config file.');

            return false;
        }

        if (str_contains($content, $marker)) {
            $this->components->warn('The resources are already registered in the Shield config.');

            return false;
        }

        $entries = '';

        foreach ($resources as $resource => $abilities) {
            $entries .= '            \\'.ltrim($resource, '\\')."::class => [\n";

            foreach ($abilities as $ability) {
                $entries .= "                '{$ability}',\n";
            }

            $entries .= "            ],\n";
        }

        $insertPos = $this->shieldManageInsertPosition($content);

        if ($insertPos === null) {
            return false;
        }

        file_put_contents($this->shieldConfigPath(), substr($content, 0, $insertPos).$entries.substr($content, $insertPos));

        return true;
    }

    /**
     * Drop every `\{marker}\...::class => [ ... ],` entry from the file.
     */
    protected function unregisterShieldResources(string $marker): bool
    {
        $content = file_get_contents($this->shieldConfigPath());

        if ($content === false || ! str_contains($content, $marker)) {
            return false;
        }

        $pattern = '#[ \t]*\\\\'.preg_quote(ltrim($marker, '\\'), '#').'\\\\[^\n]+::class\s*=>\s*\[\n(?:[ \t]+\'[^\']+\',?\n)*[ \t]*\],?\n#';
        $updated = preg_replace($pattern, '', $content);

        if ($updated === null || $updated === $content) {
            return false;
        }

        file_put_contents($this->shieldConfigPath(), $updated);

        return true;
    }

    /**
     * The byte offset of the line that closes the resources.manage array.
     */
    protected function shieldManageInsertPosition(string $content): ?int
    {
        $managePos = strpos($content, "'manage' => [");

        if ($managePos === false) {
            $this->components->warn('Could not find the manage array in the Shield config. Add the resources yourself.');

            return null;
        }

        $openBracket = strpos($content, '[', $managePos + strlen("'manage' => "));

        if ($openBracket === false) {
            $this->components->warn('Could not parse the Shield config. Add the resources yourself.');

            return null;
        }

        $depth = 1;
        $pos = $openBracket + 1;
        $len = strlen($content);

        while ($pos < $len && $depth > 0) {
            if ($content[$pos] === '[') {
                $depth++;
            } elseif ($content[$pos] === ']') {
                $depth--;
            }

            if ($depth > 0) {
                $pos++;
            }
        }

        $insertPos = strrpos(substr($content, 0, $pos), "\n");

        if ($insertPos === false) {
            $this->components->warn('Could not parse the Shield config. Add the resources yourself.');

            return null;
        }

        return $insertPos + 1;
    }
}
