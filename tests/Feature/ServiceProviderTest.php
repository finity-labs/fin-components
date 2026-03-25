<?php

declare(strict_types=1);

use FinityLabs\FinSentinel\FinSentinelServiceProvider;

beforeEach(function () {
    $reflection = new ReflectionProperty(FinSentinelServiceProvider::class, 'handling');
    $reflection->setAccessible(true);
    $reflection->setValue(null, false);
});

it('executes the callback inside guardedHandle', function () {
    $counter = 0;

    FinSentinelServiceProvider::guardedHandle(function () use (&$counter) {
        $counter++;
    });

    expect($counter)->toBe(1);
});

it('resets isHandling to false after normal execution', function () {
    FinSentinelServiceProvider::guardedHandle(function () {
        // no-op
    });

    expect(FinSentinelServiceProvider::isHandling())->toBeFalse();
});

it('prevents recursive re-entry via nested guardedHandle calls', function () {
    $counter = 0;

    FinSentinelServiceProvider::guardedHandle(function () use (&$counter) {
        $counter++;

        // This nested call should be silently skipped
        FinSentinelServiceProvider::guardedHandle(function () use (&$counter) {
            $counter++;
        });
    });

    expect($counter)->toBe(1);
});

it('resets isHandling to false even when callback throws an exception', function () {
    try {
        FinSentinelServiceProvider::guardedHandle(function () {
            throw new RuntimeException('test exception');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(FinSentinelServiceProvider::isHandling())->toBeFalse();
});

it('reports isHandling as true during callback execution', function () {
    $wasTrueDuringExecution = false;

    FinSentinelServiceProvider::guardedHandle(function () use (&$wasTrueDuringExecution) {
        $wasTrueDuringExecution = FinSentinelServiceProvider::isHandling();
    });

    expect($wasTrueDuringExecution)->toBeTrue();
    expect(FinSentinelServiceProvider::isHandling())->toBeFalse();
});

it('has the service provider registered and booted', function () {
    $providers = app()->getProviders(FinSentinelServiceProvider::class);

    expect($providers)->not->toBeEmpty();
});
