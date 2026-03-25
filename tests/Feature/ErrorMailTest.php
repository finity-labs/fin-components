<?php

declare(strict_types=1);

use FinityLabs\FinSentinel\Mail\ErrorMail;

it('has the correct envelope subject containing app name', function () {
    $mail = new ErrorMail('Test error');
    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain(config('app.name'));
});

it('uses the correct content view', function () {
    $mail = new ErrorMail('Test error');
    $content = $mail->content();

    expect($content->view)->toBe('fin-sentinel::emails.error');
});

it('extracts exception class, file, and line from Throwable', function () {
    $exception = new RuntimeException('test exception');
    $mail = new ErrorMail('test exception', $exception);

    expect($mail->exceptionClass)->toBe(RuntimeException::class);
    expect($mail->exceptionFile)->toBe(__FILE__);
    expect($mail->exceptionLine)->toBeInt();
    expect($mail->stackTrace)->toBeArray()->not->toBeEmpty();
});

it('handles null exception gracefully', function () {
    $mail = new ErrorMail('plain error message');

    expect($mail->exceptionClass)->toBeNull();
    expect($mail->exceptionFile)->toBeNull();
    expect($mail->exceptionLine)->toBeNull();
    expect($mail->stackTrace)->toBeNull();
});

it('includes expected keys in environmentContext', function () {
    $mail = new ErrorMail('Test error');

    expect($mail->environmentContext)->toHaveKeys([
        'app_env',
        'app_debug',
        'php_version',
        'laravel_version',
        'memory_peak',
        'timestamp',
    ]);

    expect($mail->environmentContext['php_version'])->toBe(PHP_VERSION);
});
