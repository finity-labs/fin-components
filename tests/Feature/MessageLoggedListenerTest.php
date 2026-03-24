<?php

declare(strict_types=1);

use FinityLabs\FinSentinel\FinSentinelServiceProvider;
use FinityLabs\FinSentinel\Mail\ErrorMail;
use FinityLabs\FinSentinel\Settings\ErrorChannelSettings;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    // Reset loop guard
    $reflection = new \ReflectionProperty(FinSentinelServiceProvider::class, 'handling');
    $reflection->setAccessible(true);
    $reflection->setValue(null, false);

    // Configure settings
    $settings = app(ErrorChannelSettings::class);
    $settings->error_recipients = ['test@example.com'];
    $settings->error_enabled = true;
    $settings->error_throttle_minutes = 15;
    $settings->error_throttle_exceptions = true;
    $settings->error_throttle_log_messages = true;
    $settings->save();

    // Clear throttle cache
    Cache::flush();
});

it('sends ErrorMail when error-level log has exception context', function () {
    Mail::fake();

    $listener = app(\FinityLabs\FinSentinel\Listeners\MessageLoggedListener::class);
    $event = new MessageLogged('error', 'Something failed', ['exception' => new \RuntimeException('test')]);

    $listener->handle($event);

    Mail::assertSent(ErrorMail::class);
});

it('does not send mail for non-error log levels', function () {
    Mail::fake();

    $listener = app(\FinityLabs\FinSentinel\Listeners\MessageLoggedListener::class);

    foreach (['info', 'warning', 'debug', 'notice'] as $level) {
        $event = new MessageLogged($level, 'Some message', []);
        $listener->handle($event);
    }

    Mail::assertNotSent(ErrorMail::class);
});

it('does not send mail for NotFoundHttpException', function () {
    Mail::fake();

    $listener = app(\FinityLabs\FinSentinel\Listeners\MessageLoggedListener::class);
    $exception = new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Not found');
    $event = new MessageLogged('error', 'Not found', ['exception' => $exception]);

    $listener->handle($event);

    Mail::assertNotSent(ErrorMail::class);
});

it('does not send mail for ValidationException', function () {
    Mail::fake();

    $listener = app(\FinityLabs\FinSentinel\Listeners\MessageLoggedListener::class);
    $exception = \Illuminate\Validation\ValidationException::withMessages(['field' => 'required']);
    $event = new MessageLogged('error', 'Validation failed', ['exception' => $exception]);

    $listener->handle($event);

    Mail::assertNotSent(ErrorMail::class);
});

it('throttles duplicate exceptions within the configured window', function () {
    Mail::fake();

    $listener = app(\FinityLabs\FinSentinel\Listeners\MessageLoggedListener::class);
    $exception = new \RuntimeException('duplicate error');
    $event = new MessageLogged('error', 'duplicate error', ['exception' => $exception]);

    $listener->handle($event);
    $listener->handle($event);

    Mail::assertSentCount(1);
});

it('does not throttle exceptions when error_throttle_exceptions is off', function () {
    $settings = app(ErrorChannelSettings::class);
    $settings->error_throttle_exceptions = false;
    $settings->save();

    Mail::fake();

    $listener = app(\FinityLabs\FinSentinel\Listeners\MessageLoggedListener::class);
    $exception = new \RuntimeException('no throttle');
    $event = new MessageLogged('error', 'no throttle', ['exception' => $exception]);

    $listener->handle($event);
    $listener->handle($event);

    Mail::assertSentCount(2);
});

it('sends mail for plain error log messages without exception', function () {
    Mail::fake();

    $listener = app(\FinityLabs\FinSentinel\Listeners\MessageLoggedListener::class);
    $event = new MessageLogged('error', 'plain error message', []);

    $listener->handle($event);

    Mail::assertSent(ErrorMail::class);
});

it('does not send mail when loop guard is active', function () {
    Mail::fake();

    // Activate loop guard
    $reflection = new \ReflectionProperty(FinSentinelServiceProvider::class, 'handling');
    $reflection->setAccessible(true);
    $reflection->setValue(null, true);

    $listener = app(\FinityLabs\FinSentinel\Listeners\MessageLoggedListener::class);
    $event = new MessageLogged('error', 'should be blocked', ['exception' => new \RuntimeException('blocked')]);

    $listener->handle($event);

    Mail::assertNotSent(ErrorMail::class);

    // Reset for cleanup
    $reflection->setValue(null, false);
});

it('does not throttle plain log messages when error_throttle_log_messages is off', function () {
    $settings = app(ErrorChannelSettings::class);
    $settings->error_throttle_log_messages = false;
    $settings->save();

    Mail::fake();

    $listener = app(\FinityLabs\FinSentinel\Listeners\MessageLoggedListener::class);
    $event = new MessageLogged('error', 'repeated plain error', []);

    $listener->handle($event);
    $listener->handle($event);

    Mail::assertSentCount(2);
});
