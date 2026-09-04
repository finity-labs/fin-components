<?php

declare(strict_types=1);

use FinityLabs\LinCodex\Rendering\ArticlePath;

it('resolves a sibling article path', function (): void {
    expect(ArticlePath::resolve('users/permissions', 'roles.md'))
        ->toBe(['slug' => 'users/roles', 'fragment' => '']);
});

it('resolves parent traversal and keeps the fragment', function (): void {
    expect(ArticlePath::resolve('users/permissions', '../billing/invoices.md#totals'))
        ->toBe(['slug' => 'billing/invoices', 'fragment' => '#totals']);
});

it('resolves nested and dot-prefixed paths from a top-level article', function (): void {
    expect(ArticlePath::resolve('intro', 'users/roles.md'))
        ->toBe(['slug' => 'users/roles', 'fragment' => ''])
        ->and(ArticlePath::resolve('intro', './roles.MD'))
        ->toBe(['slug' => 'roles', 'fragment' => '']);
});

it('refuses to climb above the article root', function (): void {
    expect(ArticlePath::resolve('intro', '../escape.md'))->toBeNull()
        ->and(ArticlePath::resolve('users/permissions', '../../escape.md'))->toBeNull();
});

it('leaves unresolvable targets alone', function (string $target): void {
    expect(ArticlePath::resolve('users/permissions', $target))->toBeNull();
})->with([
    'absolute url' => 'https://example.com/a.md',
    'protocol relative' => '//cdn/a.md',
    'root relative' => '/abs/a.md',
    'mailto' => 'mailto:x@y.z',
    'fragment only' => '#only',
    'no extension' => 'roles',
    'query string' => 'roles.md?x=1',
    'html extension' => 'roles.html',
    'space in segment' => 'we ird.md',
    'empty' => '',
]);

it('builds the help center href from config at call time', function (): void {
    expect(ArticlePath::href('users/roles', '#x'))->toBe('/help/users/roles#x');

    config()->set('lin-codex.routes.help_center', 'https://app.test/manual/');

    expect(ArticlePath::href('users/roles', '#x'))->toBe('https://app.test/manual/users/roles#x');
});
