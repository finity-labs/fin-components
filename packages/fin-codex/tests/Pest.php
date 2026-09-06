<?php

use FinityLabs\FinCodex\Help\ArticleLookup;
use FinityLabs\FinCodex\Help\DeclaredContexts;
use FinityLabs\FinCodex\Panel\CurrentPage;
use FinityLabs\FinCodex\Tests\TestCase;
use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\Sources\FilesystemSource;
use FinityLabs\LinCodex\View\PageHelpResolver;

uses(TestCase::class)->in(__DIR__);

/**
 * Drop the request-scoped memos (lin-codex's page help, fin-codex's page
 * identity and the hint lookup) and the decorated content source when a
 * test seeds articles or swaps the request after a first in-process
 * request. The ContentSource
 * extender is re-applied on the next resolution, so the fresh instance is
 * again the declared-contexts decorator, over a fresh registry scan.
 */
function forgetHelpMemo(): void
{
    app()->forgetInstance(PageHelpResolver::class);
    app()->forgetInstance(CurrentPage::class);
    app()->forgetInstance(ArticleLookup::class);
    app()->forgetInstance(ContentSource::class);
    app()->forgetInstance(DeclaredContexts::class);
}

/**
 * Point the file source at tests/Fixtures/docs for the rest of the test: a
 * three-article English tree (intro, users, users/roles) with a German
 * translation of users/roles. The config write is test-side and happens
 * before the source is resolved; forgetHelpMemo() drops the composite so the
 * next ContentSource sees the files. Nothing is imported — the rows stay
 * file-only, which is the point.
 */
function useFixtureDocs(): void
{
    config()->set('lin-codex.sources.filesystem.paths', [__DIR__.'/Fixtures/docs']);

    app()->forgetInstance(FilesystemSource::class);

    forgetHelpMemo();
}
