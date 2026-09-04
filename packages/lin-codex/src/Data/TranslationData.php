<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Data;

/**
 * One locale's title, excerpt and body for an article.
 *
 * For file sources the body has the front matter and the consumed H1 removed
 * and image paths rewritten; for the database it is the raw column. The
 * search text is the plain text extracted at scan time for files and the
 * search_text column for the database (null until Phase 5 fills it).
 */
final readonly class TranslationData
{
    public function __construct(
        public string $locale,
        public string $title,
        public ?string $excerpt,
        public string $body,
        public ?string $searchText,
        public ?string $sourcePath = null,
    ) {}
}
