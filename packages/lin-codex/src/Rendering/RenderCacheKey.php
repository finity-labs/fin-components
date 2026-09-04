<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Rendering;

use FinityLabs\LinCodex\Enums\ArticleFormat;

/**
 * Cache key for one rendered article. The key embeds the content hash, so an
 * edit produces a new key and the old entry is simply orphaned; the renderer
 * fingerprint does the same for config and extension changes. Locale and
 * slug are part of the key because translated titles and resolved article
 * links depend on them.
 */
final class RenderCacheKey
{
    public const PREFIX = 'lin-codex:render:';

    /**
     * @return string the prefix followed by a 64-character hex sha256
     */
    public static function make(string $fingerprint, string $body, ArticleFormat $format, string $locale, string $slug): string
    {
        $parts = [
            $fingerprint,
            $format->key(),
            $locale,
            $slug,
            hash('sha256', $body),
        ];

        return self::PREFIX.hash('sha256', implode('|', $parts));
    }
}
