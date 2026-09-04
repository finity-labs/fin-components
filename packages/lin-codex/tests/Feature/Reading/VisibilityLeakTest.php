<?php

declare(strict_types=1);

use FinityLabs\LinCodex\Auth\ViewerResolver;
use FinityLabs\LinCodex\Contexts\ContextResolver;
use FinityLabs\LinCodex\Contexts\PageContext;
use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\Reading\ArticleReader;
use FinityLabs\LinCodex\Reading\TreeBuilder;
use FinityLabs\LinCodex\Sources\CompositeSource;
use FinityLabs\LinCodex\Sources\DatabaseSource;
use FinityLabs\LinCodex\Sources\FilesystemSource;
use Illuminate\Auth\GenericUser;

/**
 * Switch to one of the three sources over the docs-visibility tree, seeding
 * the database twins when the source reads the database.
 */
function linCodexLeakUseSource(string $source): void
{
    config()->set('lin-codex.source', $source);

    if ($source !== 'filesystem') {
        linCodexLeakSeedDatabase();
    }

    app()->forgetInstance(FilesystemSource::class);
    app()->forgetInstance(DatabaseSource::class);
    app()->forgetInstance(CompositeSource::class);
    app()->forgetInstance(ContentSource::class);
}

beforeEach(function (): void {
    config()->set('lin-codex.sources.filesystem.paths', [$this->fixtureDocsPath('docs-visibility')]);
});

it('never leaks through the reader, the tree or the context resolver', function (string $source, string $viewer, string $slug, bool $guestSees, bool $userSees): void {
    linCodexLeakUseSource($source);

    if ($viewer === 'user') {
        $this->actingAs(new GenericUser(['id' => 1]));
    }

    $expected = $viewer === 'user' ? $userSees : $guestSees;
    $current = app(ViewerResolver::class)->resolve();

    expect(app(ArticleReader::class)->read($slug, $current) !== null)->toBe($expected)
        ->and(in_array($slug, linCodexLeakTreeSlugs(app(TreeBuilder::class)->build($current)), true))->toBe($expected)
        ->and(linCodexLeakSlugs(app(ContextResolver::class)->resolve(new PageContext(null, '/leak/'.$slug), $current)))->toBe($expected ? [$slug] : []);
})->with('lin-codex sources', 'lin-codex viewers', 'lin-codex leak articles');

it('hides a section and its public child from the guest tree wholesale', function (string $source): void {
    linCodexLeakUseSource($source);

    $guest = app(ViewerResolver::class)->resolve();

    expect(linCodexLeakTreeSlugs(app(TreeBuilder::class)->build($guest)))
        ->toBe(['group', 'group/public-child', 'only-en', 'public-published', 'shared']);

    $this->actingAs(new GenericUser(['id' => 1]));
    $user = app(ViewerResolver::class)->resolve();

    expect(linCodexLeakTreeSlugs(app(TreeBuilder::class)->build($user)))
        ->toBe(['auth-published', 'group', 'group/public-child', 'internal', 'internal/public-child', 'only-en', 'public-published', 'shared']);
})->with('lin-codex sources');

it('answers a hidden and a missing slug identically', function (string $source): void {
    linCodexLeakUseSource($source);

    $guest = app(ViewerResolver::class)->resolve();
    $reader = app(ArticleReader::class);
    $resolver = app(ContextResolver::class);

    expect($reader->read('auth-published', $guest))->toBeNull()
        ->and($reader->read('does-not-exist', $guest))->toBeNull()
        ->and($reader->read('internal/public-child', $guest))->toBeNull()
        ->and($resolver->resolve(new PageContext(null, '/leak/auth-published'), $guest))->toBe([])
        ->and($resolver->resolve(new PageContext(null, '/leak/does-not-exist'), $guest))->toBe([]);
})->with('lin-codex sources');

it('keeps the reader, tree and resolver output free of models', function (): void {
    linCodexLeakUseSource('filesystem');

    $this->actingAs(new GenericUser(['id' => 1]));
    $user = app(ViewerResolver::class)->resolve();

    linCodexAssertNoModels(app(ArticleReader::class)->read('public-published', $user));
    linCodexAssertNoModels(app(TreeBuilder::class)->build($user));
    linCodexAssertNoModels(app(ContextResolver::class)->resolve(new PageContext(null, '/leak/public-published'), $user));
});
