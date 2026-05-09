<?php

declare(strict_types=1);

use FinityLabs\FinSentinel\Facades\FinSentinel;
use FinityLabs\FinSentinel\FinSentinelServiceProvider;
use FinityLabs\FinSentinel\Mail\DebugMail;
use FinityLabs\FinSentinel\Settings\DebugChannelSettings;
use FinityLabs\FinSentinel\Support\DebugBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $reflection = new ReflectionProperty(FinSentinelServiceProvider::class, 'handling');
    $reflection->setAccessible(true);
    $reflection->setValue(null, false);

    $settings = app(DebugChannelSettings::class);
    $settings->debug_recipients = ['dev@example.com'];
    $settings->debug_enabled = true;
    $settings->debug_throttle_enabled = false;
    $settings->debug_throttle_minutes = 15;
    $settings->save();

    Cache::flush();
});

it('returns a DebugBuilder instance from debug()', function () {
    $result = FinSentinel::debug('test data');

    // Prevent destructor auto-queue from interfering
    $reflection = new ReflectionProperty(DebugBuilder::class, 'sent');
    $reflection->setAccessible(true);
    $reflection->setValue($result, true);

    expect($result)->toBeInstanceOf(DebugBuilder::class);
});

it('returns DebugBuilder with subject when provided', function () {
    Mail::fake();

    $result = FinSentinel::debug('test', 'My Subject');

    expect($result)->toBeInstanceOf(DebugBuilder::class);

    $result->send();

    Mail::assertSent(DebugMail::class, function (DebugMail $mail) {
        return $mail->customSubject === 'My Subject';
    });
});
