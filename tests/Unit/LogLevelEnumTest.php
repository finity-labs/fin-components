<?php

declare(strict_types=1);

use FinityLabs\FinSentinel\Enums\LogLevel;

it('has all 8 log level cases with correct string values', function () {
    $expected = [
        'Emergency' => 'EMERGENCY',
        'Alert' => 'ALERT',
        'Critical' => 'CRITICAL',
        'Error' => 'ERROR',
        'Warning' => 'WARNING',
        'Notice' => 'NOTICE',
        'Info' => 'INFO',
        'Debug' => 'DEBUG',
    ];

    expect(LogLevel::cases())->toHaveCount(8);

    foreach ($expected as $name => $value) {
        $case = LogLevel::from($value);
        expect($case->name)->toBe($name);
        expect($case->value)->toBe($value);
    }
});

it('returns null for invalid string via tryFrom', function () {
    expect(LogLevel::tryFrom('INVALID'))->toBeNull();
    expect(LogLevel::tryFrom(''))->toBeNull();
});
