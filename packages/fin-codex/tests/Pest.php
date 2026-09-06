<?php

use FinityLabs\FinCodex\Help\DeclaredContexts;
use FinityLabs\FinCodex\Panel\CurrentPage;
use FinityLabs\FinCodex\Tests\TestCase;
use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\View\PageHelpResolver;

uses(TestCase::class)->in(__DIR__);

/**
 * Drop the request-scoped memos (lin-codex's page help and fin-codex's page
 * identity) and the decorated content source when a test seeds articles or
 * swaps the request after a first in-process request. The ContentSource
 * extender is re-applied on the next resolution, so the fresh instance is
 * again the declared-contexts decorator, over a fresh registry scan.
 */
function forgetHelpMemo(): void
{
    app()->forgetInstance(PageHelpResolver::class);
    app()->forgetInstance(CurrentPage::class);
    app()->forgetInstance(ContentSource::class);
    app()->forgetInstance(DeclaredContexts::class);
}
