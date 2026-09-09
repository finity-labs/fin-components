<?php

declare(strict_types=1);

use FinityLabs\FinSupport\Tests\Fixtures\Pages\FallbackPage;
use FinityLabs\FinSupport\Tests\Fixtures\Pages\GatedPage;
use FinityLabs\FinSupport\Tests\Fixtures\Pages\ShieldedPage;
use FinityLabs\FinSupport\Tests\Fixtures\Pages\UnregisteredShieldedPage;
use FinityLabs\FinSupport\Tests\Fixtures\ShieldStub;
use FinityLabs\FinSupport\Tests\Fixtures\User;
use Illuminate\Support\Facades\Gate;

function finSupportSignedIn(): User
{
    $user = User::create(['name' => 'Tester', 'email' => 'tester@example.com']);

    test()->usesPanel('admin', $user);

    return $user;
}

it('leaves a page open when no gate ability is defined', function (): void {
    finSupportSignedIn();

    expect(Gate::has('page_GatedPage'))->toBeFalse()
        ->and(GatedPage::canAccess())->toBeTrue()
        ->and(GatedPage::shouldRegisterNavigation())->toBeTrue();
});

it('follows a gate ability named after the page', function (): void {
    finSupportSignedIn();

    Gate::define('page_GatedPage', fn (): bool => false);

    expect(GatedPage::canAccess())->toBeFalse()
        ->and(GatedPage::shouldRegisterNavigation())->toBeFalse();

    Gate::define('page_GatedPage', fn (): bool => true);

    expect(GatedPage::canAccess())->toBeTrue();
});

it('lets a page give its own last word through canAccessFallback()', function (): void {
    finSupportSignedIn();

    FallbackPage::$fallback = false;

    expect(FallbackPage::canAccess())->toBeFalse();

    FallbackPage::$fallback = true;

    expect(FallbackPage::canAccess())->toBeTrue();
});

it('asks shield for the page permission rather than building the name', function (): void {
    $permission = (new ReflectionMethod(ShieldedPage::class, 'getPagePermission'))->invoke(null);

    expect($permission)->toBe(ShieldStub::PERMISSION)->toBe('View:ShieldedPage');
});

it('follows shield\'s permission and ignores the gate hook while shield is available', function (): void {
    finSupportSignedIn();

    Gate::define(ShieldStub::PERMISSION, fn (): bool => false);
    Gate::define('page_ShieldedPage', fn (): bool => true);

    expect(ShieldedPage::canAccess())->toBeFalse()
        ->and(ShieldedPage::shouldRegisterNavigation())->toBeFalse();

    Gate::define(ShieldStub::PERMISSION, fn (): bool => true);
    Gate::define('page_ShieldedPage', fn (): bool => false);

    expect(ShieldedPage::canAccess())->toBeTrue();
});

it('denies the page when shield\'s permission was never granted to anyone', function (): void {
    finSupportSignedIn();

    expect(Gate::has(ShieldStub::PERMISSION))->toBeFalse()
        ->and(ShieldedPage::canAccess())->toBeFalse();
});

it('stays open when shield has no permission registered for the page', function (): void {
    finSupportSignedIn();

    expect(UnregisteredShieldedPage::canAccess())->toBeTrue();
});

it('defines no shield class anywhere in the suite', function (): void {
    expect(class_exists('BezhanSalleh\FilamentShield\FilamentShieldPlugin', false))->toBeFalse()
        ->and((new ReflectionMethod(GatedPage::class, 'isShieldAvailable'))->invoke(null))->toBeFalse();
});
