<?php

use Filament\Facades\Filament;
use FinityLabs\FinCodex\Tests\Fixtures\User;

/*
 * One panel per test method: FilamentManager is scoped and boots only the
 * first panel requested in a PHP request cycle, so the datasets below give
 * every panel its own fresh application.
 */

it('registers admin as the default panel on the web guard and staff on the staff guard', function (): void {
    expect(Filament::getDefaultPanel()->getId())->toBe('admin')
        ->and(Filament::getPanel('admin')->getAuthGuard())->toBe('web')
        ->and(Filament::getPanel('staff')->getAuthGuard())->toBe('staff')
        ->and(Filament::getPanel('plain')->getId())->toBe('plain');
});

it('renders the login page of each panel', function (string $path): void {
    $this->get($path)->assertOk();
})->with([
    'admin' => '/admin/login',
    'staff' => '/staff/login',
    'plain' => '/plain/login',
]);

it('renders the dashboard for a user signed in on the panel guard', function (string $guard, string $path): void {
    $user = User::create(['name' => 'Tester', 'email' => $guard.'@example.com']);

    $this->actingAs($user, $guard)->get($path)->assertOk();
})->with([
    'admin on web' => ['web', '/admin'],
    'staff on staff' => ['staff', '/staff'],
]);

it('redirects a guest from the staff dashboard to the staff login', function (): void {
    $this->get('/staff')->assertRedirect('/staff/login');
});

it('makes a panel current and signs in on its guard with usesPanel()', function (): void {
    $user = User::create(['name' => 'Staff', 'email' => 'staff@example.com']);

    $panel = $this->usesPanel('staff', $user);

    expect($panel->getId())->toBe('staff')
        ->and(Filament::getCurrentPanel()?->getId())->toBe('staff')
        ->and(auth('staff')->check())->toBeTrue()
        ->and(auth('web')->check())->toBeFalse();
});
