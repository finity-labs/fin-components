<?php

declare(strict_types=1);

use FinityLabs\FinSupport\Auth\PolicyRegistrar;
use FinityLabs\FinSupport\Tests\Fixtures\Models\Thing;
use FinityLabs\FinSupport\Tests\Fixtures\Models\Widget;
use FinityLabs\FinSupport\Tests\Fixtures\Policies\HostThingPolicy;
use FinityLabs\FinSupport\Tests\Fixtures\Policies\ShippedThingPolicy;
use Illuminate\Support\Facades\Gate;

function finSupportHostPolicy(): void
{
    if (! class_exists('FinSupportHost\\Policies\\ThingPolicy', false)) {
        class_alias(HostThingPolicy::class, 'FinSupportHost\\Policies\\ThingPolicy');
    }
}

it('registers the host policy from the namespace when it exists', function (): void {
    finSupportHostPolicy();

    $registered = PolicyRegistrar::register('FinSupportHost\\Policies\\', [Thing::class => 'ThingPolicy']);

    expect($registered)->toBe([Thing::class => 'FinSupportHost\\Policies\\ThingPolicy'])
        ->and(Gate::getPolicyFor(Thing::class))->toBeInstanceOf(HostThingPolicy::class);
});

it('falls back to the shipped policy when the host has none, and registers nothing without one', function (): void {
    $registered = PolicyRegistrar::register('App\\Policies', [
        Thing::class => 'ThingPolicy',
        Widget::class => 'WidgetPolicy',
    ], [Thing::class => ShippedThingPolicy::class]);

    expect($registered)->toBe([Thing::class => ShippedThingPolicy::class])
        ->and(Gate::getPolicyFor(Thing::class))->toBeInstanceOf(ShippedThingPolicy::class)
        ->and(Gate::getPolicyFor(Widget::class))->toBeNull();
});

it('prefers the host policy over the shipped one', function (): void {
    finSupportHostPolicy();

    PolicyRegistrar::register('FinSupportHost\\Policies', [Thing::class => 'ThingPolicy'], [Thing::class => ShippedThingPolicy::class]);

    expect(Gate::getPolicyFor(Thing::class))->toBeInstanceOf(HostThingPolicy::class);
});

it('reads the namespace off the panel plugin, and the default when there is none', function (): void {
    expect(PolicyRegistrar::namespaceOf('fixture'))->toBe('Fixture\\Policies')
        ->and(PolicyRegistrar::namespaceOf('missing'))->toBe(PolicyRegistrar::DEFAULT_NAMESPACE)
        ->and(PolicyRegistrar::namespaceOf('missing', 'Other\\Policies'))->toBe('Other\\Policies');
});
