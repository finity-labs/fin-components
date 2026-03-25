<?php

declare(strict_types=1);

use FinityLabs\FinSentinel\Events\SentinelDebug;
use FinityLabs\FinSentinel\FinSentinelServiceProvider;
use FinityLabs\FinSentinel\Listeners\SentinelDebugListener;
use FinityLabs\FinSentinel\Mail\DebugMail;
use FinityLabs\FinSentinel\Settings\DebugChannelSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    // Reset loop guard
    $reflection = new ReflectionProperty(FinSentinelServiceProvider::class, 'handling');
    $reflection->setAccessible(true);
    $reflection->setValue(null, false);

    // Configure debug settings
    $settings = app(DebugChannelSettings::class);
    $settings->debug_recipients = ['dev@example.com'];
    $settings->debug_enabled = true;
    $settings->debug_throttle_enabled = false;
    $settings->debug_throttle_minutes = 15;
    $settings->save();

    Cache::flush();
});

it('queues debug mail when enabled with recipients', function () {
    Mail::fake();

    $event = new SentinelDebug(['key' => 'value']);
    $listener = app(SentinelDebugListener::class);
    $listener->handle($event);

    Mail::assertQueued(DebugMail::class);
});

it('skips sending when debug_enabled is false', function () {
    Mail::fake();

    $settings = app(DebugChannelSettings::class);
    $settings->debug_enabled = false;
    $settings->save();

    $event = new SentinelDebug(['key' => 'value']);
    $listener = app(SentinelDebugListener::class);
    $listener->handle($event);

    Mail::assertNotQueued(DebugMail::class);
});

it('skips sending when no recipients configured', function () {
    Mail::fake();

    $settings = app(DebugChannelSettings::class);
    $settings->debug_recipients = [];
    $settings->save();

    $event = new SentinelDebug(['key' => 'value']);
    $listener = app(SentinelDebugListener::class);
    $listener->handle($event);

    Mail::assertNotQueued(DebugMail::class);
});

it('throttles duplicate events when throttle is enabled', function () {
    Mail::fake();

    $settings = app(DebugChannelSettings::class);
    $settings->debug_throttle_enabled = true;
    $settings->save();

    $listener = app(SentinelDebugListener::class);
    $event = new SentinelDebug(['same' => 'data']);

    $listener->handle($event);

    // Reset the loop guard between calls
    $reflection = new ReflectionProperty(FinSentinelServiceProvider::class, 'handling');
    $reflection->setAccessible(true);
    $reflection->setValue(null, false);

    $listener->handle($event);

    Mail::assertQueued(DebugMail::class, 1);
});

it('does not throttle when throttle is disabled', function () {
    Mail::fake();

    $listener = app(SentinelDebugListener::class);
    $event = new SentinelDebug(['same' => 'data']);

    $listener->handle($event);

    // Reset the loop guard between calls
    $reflection = new ReflectionProperty(FinSentinelServiceProvider::class, 'handling');
    $reflection->setAccessible(true);
    $reflection->setValue(null, false);

    $listener->handle($event);

    Mail::assertQueued(DebugMail::class, 2);
});

it('scrubs sensitive data from formatted output', function () {
    Mail::fake();

    $event = new SentinelDebug(['username' => 'john', 'password' => 'secret']);
    $listener = app(SentinelDebugListener::class);
    $listener->handle($event);

    Mail::assertQueued(DebugMail::class, function (DebugMail $mail) {
        return ($mail->formattedData['data']['password'] ?? null) === '[REDACTED]'
            && ($mail->formattedData['data']['username'] ?? null) === 'john';
    });
});

it('passes custom subject to DebugMail', function () {
    Mail::fake();

    $event = new SentinelDebug(['key' => 'value'], 'Test Debug');
    $listener = app(SentinelDebugListener::class);
    $listener->handle($event);

    Mail::assertQueued(DebugMail::class, function (DebugMail $mail) {
        return $mail->customSubject === 'Test Debug';
    });
});
