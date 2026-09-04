<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Data;

use FinityLabs\LinCodex\Enums\Visibility;

/**
 * One searchable unit: an article in one locale. The text is the search
 * text with the keywords folded in, so an index built from these documents
 * finds an article by a keyword that never appears in its body.
 */
final readonly class SearchDocument
{
    public function __construct(
        public string $slug,
        public string $locale,
        public string $title,
        public ?string $excerpt,
        public string $text,
        public Visibility $visibility,
        public bool $published,
    ) {}
}
