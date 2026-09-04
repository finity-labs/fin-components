<?php

declare(strict_types=1);

use FinityLabs\LinCodex\Rendering\Markdown\MarkdownPipeline;

it('keeps resolved article links internal when the help center is an absolute url on the app host', function (): void {
    config()->set('app.url', 'http://localhost');
    config()->set('lin-codex.routes.help_center', 'http://localhost/manual/');

    $html = (new MarkdownPipeline)->render('[Roles](roles.md)', 'en', 'intro')->html;

    expect($html)->toBe('<p><a data-codex-article="roles" href="http://localhost/manual/roles">Roles</a></p>'."\n")
        ->not->toContain('target=')
        ->not->toContain('rel=')
        ->not->toContain('codex-external');
});

it('resolves links against the slug of each render, not the first one', function (): void {
    $pipeline = new MarkdownPipeline;
    $body = '[Roles](roles.md)';

    expect($pipeline->render($body, 'en', 'users/permissions')->html)->toContain('data-codex-article="users/roles"')
        ->and($pipeline->render($body, 'en', 'billing/intro')->html)->toContain('data-codex-article="billing/roles"');
});

it('keeps the link text and not the href in the plain text', function (): void {
    $result = (new MarkdownPipeline)->render('See [Roles](roles.md) for details.', 'en', 'users/permissions');

    expect($result->plainText)->toContain('Roles')
        ->not->toContain('/help');
});
