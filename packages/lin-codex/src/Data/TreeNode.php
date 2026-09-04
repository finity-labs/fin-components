<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Data;

/**
 * One node of the article tree. A node without an article is a group: a
 * folder that holds articles but has no index file of its own. The label is
 * derived from the slug; Phase 4 swaps in the translated title itself.
 */
final readonly class TreeNode
{
    /**
     * @param  list<TreeNode>  $children
     */
    public function __construct(
        public string $slug,
        public string $label,
        public ?ArticleData $article,
        public array $children = [],
    ) {}
}
