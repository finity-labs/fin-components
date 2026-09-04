<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams the images file articles reference ("{media}/{locale}/{path}")
 * from the configured docs paths.
 *
 * The extension allowlist is checked before any disk access, so a request
 * for ".env" or "01-intro.md" never reaches realpath(). realpath() resolves
 * ".." and symlinks, so the containment check compares canonical paths and
 * refuses anything that lands outside a docs path, in plain or URL-encoded
 * spelling. Later paths win, the same rule articles follow. Laravel only
 * calls isNotModified() inside the SetCacheHeaders middleware, so the
 * controller calls it itself to answer a conditional GET with 304.
 *
 * Visibility gating of an image by the owning article is Phase 4's
 * concern; this controller serves any image under a docs path.
 */
final class MediaController extends Controller
{
    /**
     * Extension to MIME type. SVG is deliberately absent: inline SVG can
     * carry scripts.
     *
     * @var array<string, string>
     */
    private const MIME = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
    ];

    public function __invoke(Request $request, string $locale, string $path): Response
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! isset(self::MIME[$extension])) {
            abort(404);
        }

        $file = $this->locate($locale, $path) ?? abort(404);

        $response = response()->file($file, [
            'Content-Type' => self::MIME[$extension],
            'Cache-Control' => 'public, max-age=86400',
        ])->setAutoEtag();

        $response->isNotModified($request);

        return $response;
    }

    /**
     * The canonical path of the image inside the last configured docs path
     * that holds it, or null when no path holds it or it is outside.
     */
    private function locate(string $locale, string $path): ?string
    {
        $paths = config('lin-codex.sources.filesystem.paths', []);
        $paths = is_array($paths) ? array_values(array_filter($paths, 'is_string')) : [];

        foreach (array_reverse($paths) as $docsPath) {
            $root = realpath($docsPath);

            if ($root === false) {
                continue;
            }

            $candidate = realpath($root.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path));

            if ($candidate === false || ! is_file($candidate) || ! str_starts_with($candidate, $root.DIRECTORY_SEPARATOR)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }
}
