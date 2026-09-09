<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Console\Concerns;

/**
 * Registering a plugin in a host's panel provider, and taking it out again,
 * by editing the file: an install command adds `{Plugin}::make(),` to the
 * `->plugins([...])` block (creating the block before `->middleware([` when
 * there is none) and the matching `use` line; an uninstall command removes
 * the call with every chained option and the import. Both are safe to
 * repeat: a second registration is refused, a missing one is reported.
 *
 * Every message names the plugin's class basename, so one trait serves any
 * plugin. Line endings are normalised to \n on the way in.
 */
trait EditsPanelProviders
{
    /**
     * @param  class-string  $pluginClass
     */
    protected function registerPlugin(string $panelPath, string $pluginClass): bool
    {
        $content = file_get_contents($panelPath);

        if ($content === false) {
            $this->components->error("Could not read file: {$panelPath}");

            return false;
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $basename = class_basename($pluginClass);
        $pluginCall = $basename.'::make()';
        $importLine = 'use '.ltrim($pluginClass, '\\').';';

        if (str_contains($content, $pluginCall)) {
            $this->components->warn("{$basename} is already registered in this panel provider.");

            return false;
        }

        if (! str_contains($content, $importLine)) {
            $content = $this->addImport($content, $importLine);
        }

        $content = str_contains($content, '->plugins([')
            ? $this->appendToExistingPlugins($content, $pluginCall)
            : $this->createPluginsBlock($content, $pluginCall, $basename);

        if (! str_contains($content, $pluginCall)) {
            return false;
        }

        file_put_contents($panelPath, $content);
        $this->components->info("{$basename} has been registered in the panel provider.");

        return true;
    }

    /**
     * @param  class-string  $pluginClass
     */
    protected function deregisterPlugin(string $panelPath, string $pluginClass): bool
    {
        $content = file_get_contents($panelPath);

        if ($content === false) {
            $this->components->error("Could not read file: {$panelPath}");

            return false;
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $basename = class_basename($pluginClass);
        $pluginCall = $basename.'::make()';
        $importLine = 'use '.ltrim($pluginClass, '\\').";\n";

        if (! str_contains($content, $pluginCall)) {
            $this->components->warn("{$basename} is not registered in this panel provider.");

            return false;
        }

        $content = $this->removePluginBlock($content, $pluginCall);

        // An emptied plugins block goes too.
        $content = (string) preg_replace('/\s*->plugins\(\[\s*\]\)\n?/', "\n", $content);
        $content = str_replace($importLine, '', $content);

        file_put_contents($panelPath, $content);
        $this->components->info("{$basename} has been removed from the panel provider.");

        return true;
    }

    protected function addImport(string $content, string $importLine): string
    {
        $lines = explode("\n", $content);
        $lastUseIndex = null;

        foreach ($lines as $index => $line) {
            if (preg_match('/^use\s+/', trim($line)) === 1) {
                $lastUseIndex = $index;
            }
        }

        if ($lastUseIndex === null) {
            return $content;
        }

        array_splice($lines, $lastUseIndex + 1, 0, [$importLine]);

        return implode("\n", $lines);
    }

    protected function appendToExistingPlugins(string $content, string $pluginCall): string
    {
        $pos = strpos($content, '->plugins([');

        if ($pos === false) {
            return $content;
        }

        $insertPos = strpos($content, '[', $pos);

        if ($insertPos === false) {
            return $content;
        }

        return substr_replace($content, "\n".$this->indentOfLineAt($content, $pos).'    '.$pluginCall.',', $insertPos + 1, 0);
    }

    protected function createPluginsBlock(string $content, string $pluginCall, string $basename): string
    {
        $pos = strpos($content, '->middleware([');

        if ($pos === false) {
            $pos = strpos($content, '->authMiddleware([');
        }

        if ($pos === false) {
            $this->components->warn("Could not find a place for ->plugins() in the panel provider. Register {$basename}::make() yourself.");

            return $content;
        }

        $lineStart = strrpos(substr($content, 0, $pos), "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;
        $indent = $this->indentOfLineAt($content, $pos);

        $block = $indent."->plugins([\n"
            .$indent.'    '.$pluginCall.",\n"
            .$indent."])\n";

        return substr_replace($content, $block, $lineStart, 0);
    }

    /**
     * Remove `{Plugin}::make()` with every chained `->option(...)` call, its
     * trailing comma and its line, by walking balanced parentheses (strings
     * respected), so a multi-line registration goes as cleanly as a one-liner.
     */
    protected function removePluginBlock(string $content, string $pluginCall): string
    {
        $start = strpos($content, $pluginCall);

        if ($start === false) {
            return $content;
        }

        $blockStart = $start;

        while ($blockStart > 0 && $content[$blockStart - 1] === ' ') {
            $blockStart--;
        }

        if ($blockStart > 0 && $content[$blockStart - 1] === "\n") {
            $blockStart--;
        }

        $pos = $start + strlen($pluginCall);
        $len = strlen($content);

        while ($pos < $len) {
            while ($pos < $len && in_array($content[$pos], [' ', "\t", "\n", "\r"], true)) {
                $pos++;
            }

            if ($pos + 1 < $len && $content[$pos] === '-' && $content[$pos + 1] === '>') {
                $pos += 2;

                while ($pos < $len && $content[$pos] !== '(') {
                    $pos++;
                }

                if ($pos < $len) {
                    $pos = $this->skipBalanced($content, $pos);
                }

                continue;
            }

            break;
        }

        if ($pos < $len && $content[$pos] === ',') {
            $pos++;
        }

        if ($pos < $len && $content[$pos] === "\n") {
            $pos++;
        }

        return substr($content, 0, $blockStart).substr($content, $pos);
    }

    private function skipBalanced(string $content, int $pos): int
    {
        $depth = 0;
        $len = strlen($content);
        $inString = false;
        $stringChar = '';

        while ($pos < $len) {
            $char = $content[$pos];

            if ($inString) {
                if ($char === '\\') {
                    $pos += 2;

                    continue;
                }

                if ($char === $stringChar) {
                    $inString = false;
                }

                $pos++;

                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $stringChar = $char;
                $pos++;

                continue;
            }

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;

                if ($depth === 0) {
                    return $pos + 1;
                }
            }

            $pos++;
        }

        return $pos;
    }

    private function indentOfLineAt(string $content, int $pos): string
    {
        $lineStart = strrpos(substr($content, 0, $pos), "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;

        preg_match('/^(\s*)/', substr($content, $lineStart, $pos - $lineStart), $matches);

        return $matches[1] ?? '';
    }
}
