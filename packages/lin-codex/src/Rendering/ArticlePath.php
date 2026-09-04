<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Rendering;

/**
 * Resolves relative Markdown file links ("roles.md", "../users/roles.md#x")
 * against the slug of the article being rendered and turns the resulting
 * slug into a help center URL. Pure: no state, no constructor.
 */
final class ArticlePath
{
    /**
     * Resolve a link target written as a relative .md path. Returns null
     * when the target is not an article link and must stay as written:
     * absolute or root-relative URLs, fragments, queries, non-.md files,
     * odd segment characters, or traversal above the article root.
     *
     * @return array{slug: string, fragment: string}|null
     */
    public static function resolve(string $currentSlug, string $target): ?array
    {
        if (preg_match('~^([a-z][a-z0-9+.\-]*:|//|/)~i', $target) === 1) {
            return null;
        }

        $hashPosition = strpos($target, '#');
        $path = $hashPosition === false ? $target : substr($target, 0, $hashPosition);
        $fragment = $hashPosition === false ? '' : substr($target, $hashPosition);

        if ($path === '' || str_contains($path, '?')) {
            return null;
        }

        if (strtolower(substr($path, -3)) !== '.md') {
            return null;
        }

        $path = substr($path, 0, -3);

        $segments = explode('/', $currentSlug);
        array_pop($segments);

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if ($segments === []) {
                    return null;
                }

                array_pop($segments);

                continue;
            }

            if (preg_match('/^[a-z0-9][a-z0-9-]*$/i', $segment) !== 1) {
                return null;
            }

            $segments[] = strtolower($segment);
        }

        if ($segments === []) {
            return null;
        }

        return ['slug' => implode('/', $segments), 'fragment' => $fragment];
    }

    /**
     * The help center URL for a slug, read from config at call time.
     */
    public static function href(string $slug, string $fragment = ''): string
    {
        $prefix = (string) config('lin-codex.routes.help_center', '/help');

        return rtrim($prefix, '/').'/'.$slug.$fragment;
    }
}
