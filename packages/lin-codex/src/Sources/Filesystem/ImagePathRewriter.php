<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Sources\Filesystem;

use FinityLabs\LinCodex\Enums\ArticleFormat;

/**
 * Rewrites relative image references in an article body to the media route
 * ("{media}/{locale}/{path}") by lexical path normalisation. Nothing here
 * touches the disk (no canonical path lookup, no existence check), so a
 * screenshot added after a scan is served without a rescan. Containment is
 * enforced here lexically (an image must sit inside a locale folder) and
 * again by canonical path in the media controller. Absolute, scheme, protocol-relative
 * and fragment targets stay as written; reference-style Markdown images are
 * left alone. Pure.
 */
final class ImagePathRewriter
{
    private const NOT_RELATIVE = '~^([a-z][a-z0-9+.\-]*:|//|/|#)~i';

    private const MARKDOWN_IMAGE = '/(!\[[^\]]*\]\(\s*<?)([^\s()<>]+)(>?(?:\s+"[^"]*"|\s+\'[^\']*\')?\s*\))/';

    private const HTML_IMAGE = '/(<img\b[^>]*\bsrc=)(["\'])([^"\']*)\2/i';

    /**
     * @param  string  $relativeDir  the article file's folder relative to the docs root with original folder names: "en", "en/02-users"
     * @param  string  $mediaPrefix  config('lin-codex.routes.media'), e.g. "/codex/media"
     */
    public static function rewrite(string $body, ArticleFormat $format, string $relativeDir, string $mediaPrefix): string
    {
        $prefix = rtrim($mediaPrefix, '/');

        return match ($format) {
            ArticleFormat::Markdown => preg_replace_callback(
                self::MARKDOWN_IMAGE,
                static function (array $matches) use ($relativeDir, $prefix): string {
                    $resolved = self::resolve($relativeDir, $matches[2]);

                    return $matches[1].($resolved === null ? $matches[2] : $prefix.'/'.$resolved).$matches[3];
                },
                $body,
            ) ?? $body,
            ArticleFormat::Html => preg_replace_callback(
                self::HTML_IMAGE,
                static function (array $matches) use ($relativeDir, $prefix): string {
                    $resolved = self::resolve($relativeDir, $matches[3]);

                    return $matches[1].$matches[2].($resolved === null ? $matches[3] : $prefix.'/'.$resolved).$matches[2];
                },
                $body,
            ) ?? $body,
        };
    }

    /**
     * The normalised docs-relative path ("en/images/reset.png?v=2") or null
     * when the target must stay as written: not a relative path, climbs out
     * of the docs root, or ends up outside a locale folder (fewer than two
     * segments), because the route is {media}/{locale}/{path}.
     */
    public static function resolve(string $relativeDir, string $target): ?string
    {
        if ($target === '' || preg_match(self::NOT_RELATIVE, $target) === 1) {
            return null;
        }

        $cut = strcspn($target, '?#');
        $path = substr($target, 0, $cut);
        $suffix = substr($target, $cut);

        if ($path === '') {
            return null;
        }

        $segments = [];

        foreach (explode('/', $relativeDir.'/'.$path) as $segment) {
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

            $segments[] = $segment;
        }

        if (count($segments) < 2) {
            return null;
        }

        return implode('/', $segments).$suffix;
    }
}
