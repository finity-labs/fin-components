<?php

declare(strict_types=1);

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

it('sends debug mail via send()', function () {
    Mail::fake();

    $builder = new DebugBuilder(['test' => 'data']);
    $builder->send();

    Mail::assertSent(DebugMail::class);
});

it('sets custom subject via subject()', function () {
    Mail::fake();

    (new DebugBuilder('data'))->subject('Custom')->send();

    Mail::assertSent(DebugMail::class, function (DebugMail $mail) {
        return $mail->customSubject === 'Custom';
    });
});

it('overrides recipients via to()', function () {
    Mail::fake();

    (new DebugBuilder('data'))->to('other@example.com')->send();

    Mail::assertSent(DebugMail::class, function (DebugMail $mail) {
        return collect($mail->to)->pluck('address')->contains('other@example.com');
    });
});

it('skips sending when debug_enabled is false', function () {
    Mail::fake();

    $settings = app(DebugChannelSettings::class);
    $settings->debug_enabled = false;
    $settings->save();

    (new DebugBuilder('data'))->send();

    Mail::assertNotSent(DebugMail::class);
});

it('skips sending when no recipients and no to() override', function () {
    Mail::fake();

    $settings = app(DebugChannelSettings::class);
    $settings->debug_recipients = [];
    $settings->save();

    (new DebugBuilder('data'))->send();

    Mail::assertNotSent(DebugMail::class);
});

it('only sends once when send() is called twice', function () {
    Mail::fake();

    $builder = new DebugBuilder('data');
    $builder->send();
    $builder->send();

    Mail::assertSent(DebugMail::class, 1);
});

it('auto-queues via destructor when send() was never called', function () {
    Mail::fake();

    $builder = new DebugBuilder(['auto' => 'queue']);
    unset($builder);

    Mail::assertQueued(DebugMail::class);
});

it('throttles when throttle is enabled', function () {
    Mail::fake();

    $settings = app(DebugChannelSettings::class);
    $settings->debug_throttle_enabled = true;
    $settings->save();

    $builder1 = new DebugBuilder(['same' => 'data']);
    $builder1->send();

    // Reset the loop guard between calls
    $reflection = new ReflectionProperty(FinSentinelServiceProvider::class, 'handling');
    $reflection->setAccessible(true);
    $reflection->setValue(null, false);

    $builder2 = new DebugBuilder(['same' => 'data']);
    $builder2->send();

    Mail::assertSent(DebugMail::class, 1);
});
