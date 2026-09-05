<?php

use FinityLabs\FinCodex\Panel\CurrentPage;
use FinityLabs\FinCodex\Tests\TestCase;
use FinityLabs\LinCodex\View\PageHelpResolver;

uses(TestCase::class)->in(__DIR__);

/**
 * Drop the request-scoped memos (lin-codex's page help and fin-codex's page identity) when a test seeds
 * articles or swaps the request after a first in-process request.
 */
function forgetHelpMemo(): void
{
    app()->forgetInstance(PageHelpResolver::class);
    app()->forgetInstance(CurrentPage::class);
}
