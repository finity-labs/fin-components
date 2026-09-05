<?php

use FinityLabs\FinCodex\Tests\Fixtures\User;

/*
 * One panel per test method (see PanelsTest): FilamentManager boots only the
 * first panel of a request cycle, so each dataset row gets a fresh application.
 */

function markerCount(string $html, string $panel): int
{
    return substr_count($html, 'data-fin-codex-panel="'.$panel.'"');
}

it('renders exactly one marker with the panel\'s own options on the login page', function (string $path, string $panel, string $shortcut, string $other): void {
    $html = $this->get($path)->assertOk()->getContent();

    expect(markerCount($html, $panel))->toBe(1)
        ->and(markerCount($html, $other))->toBe(0)
        ->and($html)->toContain('data-fin-codex-shortcut="'.$shortcut.'"');
})->with([
    'admin' => ['/admin/login', 'admin', 'ctrl+/', 'staff'],
    'staff' => ['/staff/login', 'staff', 'ctrl+.', 'admin'],
]);

it('renders exactly one marker on an authenticated page under the panel guard', function (string $guard, string $path, string $panel, string $other): void {
    $user = User::create(['name' => 'Tester', 'email' => $guard.'@example.com']);

    $html = $this->actingAs($user, $guard)->get($path)->assertOk()->getContent();

    expect(markerCount($html, $panel))->toBe(1)
        ->and(markerCount($html, $other))->toBe(0);
})->with([
    'admin on web' => ['web', '/admin', 'admin', 'staff'],
    'staff on staff' => ['staff', '/staff', 'staff', 'admin'],
]);

it('renders no marker in a panel without the plugin', function (): void {
    expect($this->get('/plain/login')->assertOk()->getContent())->not->toContain('data-fin-codex-panel');
});
