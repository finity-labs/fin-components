<?php

declare(strict_types=1);

use FinityLabs\LinCodex\Enums\ArticleFormat;
use FinityLabs\LinCodex\Enums\ContextType;
use FinityLabs\LinCodex\Enums\FallbackBehaviour;
use FinityLabs\LinCodex\Enums\RevisionReason;
use FinityLabs\LinCodex\Enums\Visibility;

dataset('enum backing values', [
    'ArticleFormat' => [ArticleFormat::class, [
        'Markdown' => 'markdown',
        'Html' => 'html',
    ]],
    'Visibility' => [Visibility::class, [
        'Public' => 'public',
        'Authenticated' => 'authenticated',
    ]],
    'ContextType' => [ContextType::class, [
        'PageClass' => 'class',
        'Route' => 'route',
        'Url' => 'url',
    ]],
    'RevisionReason' => [RevisionReason::class, [
        'Manual' => 'manual',
        'Import' => 'import',
        'AiRewrite' => 'ai_rewrite',
    ]],
    'FallbackBehaviour' => [FallbackBehaviour::class, [
        'ShowDefault' => 'show_default',
        'Hide' => 'hide',
    ]],
]);

it('exposes the locked backing values and no extra cases', function (string $enum, array $expected) {
    /** @var array<int, BackedEnum> $cases */
    $cases = $enum::cases();

    $actual = [];
    foreach ($cases as $case) {
        $actual[$case->name] = $case->value;
    }

    expect($actual)->toBe($expected);
})->with('enum backing values');

it('resolves the class context type from its backing value', function () {
    expect(ContextType::from('class'))->toBe(ContextType::PageClass);
});

it('labels every case through the lin-codex translation namespace', function (string $enum) {
    /** @var array<int, BackedEnum> $cases */
    $cases = $enum::cases();

    foreach ($cases as $case) {
        $label = $case->label();

        expect($label)->toBeString()->not->toBeEmpty()
            ->and($label)->not->toStartWith('lin-codex::');
    }
})->with([
    ArticleFormat::class,
    Visibility::class,
    ContextType::class,
    RevisionReason::class,
    FallbackBehaviour::class,
]);

it('spot-checks known English labels', function () {
    expect(Visibility::Public->label())->toBe('Public')
        ->and(ContextType::PageClass->label())->toBe('Page class')
        ->and(ArticleFormat::Html->label())->toBe('HTML')
        ->and(RevisionReason::AiRewrite->label())->toBe('AI rewrite')
        ->and(FallbackBehaviour::ShowDefault->label())->toBe('Show default language');
});

it('exposes users_table and media config with the locked defaults', function () {
    expect(config('lin-codex.users_table'))->toBe('users')
        ->and(config('lin-codex.media.disk'))->toBe('public')
        ->and(config('lin-codex.media.directory'))->toBe('codex');
});

it('keeps the five codex_ table names untouched', function () {
    expect(config('lin-codex.table_names'))->toBe([
        'articles' => 'codex_articles',
        'article_translations' => 'codex_article_translations',
        'article_contexts' => 'codex_article_contexts',
        'article_revisions' => 'codex_article_revisions',
        'media' => 'codex_media',
    ]);
});
