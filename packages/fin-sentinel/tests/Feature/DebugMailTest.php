<?php

declare(strict_types=1);

use FinityLabs\FinSentinel\Mail\DebugMail;

it('has envelope subject with app name and Debug default', function () {
    config()->set('app.name', 'TestApp');

    $mail = new DebugMail(
        formattedData: ['type' => 'scalar', 'value' => 'test'],
        callSite: ['file' => 'test.php', 'line' => 42],
        requestContext: [],
        environmentContext: [],
    );

    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('TestApp');
    expect($envelope->subject)->toContain('Debug');
});

it('uses custom subject in envelope when provided', function () {
    config()->set('app.name', 'TestApp');

    $mail = new DebugMail(
        formattedData: ['type' => 'scalar', 'value' => 'test'],
        callSite: ['file' => 'test.php', 'line' => 42],
        requestContext: [],
        environmentContext: [],
        customSubject: 'My Debug',
    );

    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('My Debug');
    expect($envelope->subject)->not->toContain(': Debug');
});

it('uses the correct Blade view for content rendering', function () {
    $mail = new DebugMail(
        formattedData: ['type' => 'scalar', 'value' => 'test'],
        callSite: ['file' => 'test.php', 'line' => 42],
        requestContext: [],
        environmentContext: [],
    );

    $content = $mail->content();

    expect($content->view)->toBe('fin-sentinel::emails.debug');
});

it('stores all constructor parameters as public properties', function () {
    $formattedData = ['type' => 'array', 'data' => ['key' => 'val']];
    $callSite = ['file' => '/app/test.php', 'line' => 100];
    $requestContext = ['url' => '/test', 'method' => 'GET'];
    $environmentContext = ['app_env' => 'testing', 'php_version' => PHP_VERSION];

    $mail = new DebugMail(
        formattedData: $formattedData,
        callSite: $callSite,
        requestContext: $requestContext,
        environmentContext: $environmentContext,
        customSubject: 'Test Subject',
    );

    expect($mail->formattedData)->toBe($formattedData);
    expect($mail->callSite)->toBe($callSite);
    expect($mail->requestContext)->toBe($requestContext);
    expect($mail->environmentContext)->toBe($environmentContext);
    expect($mail->customSubject)->toBe('Test Subject');
});

it('stores customSubject as null when not provided', function () {
    $mail = new DebugMail(
        formattedData: [],
        callSite: ['file' => 'test.php', 'line' => 1],
        requestContext: [],
        environmentContext: [],
    );

    expect($mail->customSubject)->toBeNull();
});

it('stores non-empty context arrays correctly', function () {
    $requestContext = ['url' => '/test', 'method' => 'GET'];
    $environmentContext = ['app_env' => 'testing', 'php_version' => PHP_VERSION];

    $mail = new DebugMail(
        formattedData: [],
        callSite: ['file' => 'test.php', 'line' => 1],
        requestContext: $requestContext,
        environmentContext: $environmentContext,
    );

    expect($mail->requestContext)->toBe($requestContext);
    expect($mail->environmentContext)->toBe($environmentContext);
});
