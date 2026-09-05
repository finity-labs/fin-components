<?php

use Filament\Facades\Filament;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

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

it('registers the fixture resource, the custom page and the guest auth routes on both plugin panels', function (): void {
    foreach (['admin', 'staff'] as $panel) {
        expect(Route::has('filament.'.$panel.'.resources.users.index'))->toBeTrue($panel.' users.index')
            ->and(Route::has('filament.'.$panel.'.resources.users.create'))->toBeTrue($panel.' users.create')
            ->and(Route::has('filament.'.$panel.'.resources.users.edit'))->toBeTrue($panel.' users.edit')
            ->and(Route::has('filament.'.$panel.'.pages.reports'))->toBeTrue($panel.' pages.reports')
            ->and(Route::has('filament.'.$panel.'.auth.register'))->toBeTrue($panel.' auth.register')
            ->and(Route::has('filament.'.$panel.'.auth.password-reset.request'))->toBeTrue($panel.' password-reset.request')
            ->and(Route::has('filament.'.$panel.'.auth.password-reset.reset'))->toBeTrue($panel.' password-reset.reset');
    }
});

it('renders the resource pages and the custom page for a user signed in on the panel guard', function (string $guard, string $path): void {
    $user = User::create(['name' => 'Tester', 'email' => $guard.'@example.com']);

    $this->actingAs($user, $guard)->get($path)->assertOk();
})->with([
    'admin users index' => ['web', '/admin/users'],
    'admin users create' => ['web', '/admin/users/create'],
    'admin users edit' => ['web', '/admin/users/1/edit'],
    'admin reports' => ['web', '/admin/reports'],
    'staff users index' => ['staff', '/staff/users'],
    'staff reports' => ['staff', '/staff/reports'],
]);

it('renders the registration and password-reset pages for a guest', function (string $path): void {
    $this->get($path)->assertOk();
})->with([
    '/admin/register',
    '/admin/password-reset/request',
    '/staff/register',
    '/staff/password-reset/request',
]);

it('renders the signed password-reset page for a guest', function (): void {
    $url = URL::signedRoute('filament.admin.auth.password-reset.reset', [
        'email' => 'guest@example.com',
        'token' => 'token',
    ]);

    $this->get($url)->assertOk();
});
