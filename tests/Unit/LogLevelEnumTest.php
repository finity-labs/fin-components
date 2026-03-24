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

it('returns danger color for emergency, alert, critical, and error', function () {
    expect(LogLevel::Emergency->color())->toBe('danger');
    expect(LogLevel::Alert->color())->toBe('danger');
    expect(LogLevel::Critical->color())->toBe('danger');
    expect(LogLevel::Error->color())->toBe('danger');
});

it('returns warning color for warning level', function () {
    expect(LogLevel::Warning->color())->toBe('warning');
});

it('returns info color for notice level', function () {
    expect(LogLevel::Notice->color())->toBe('info');
});

it('returns success color for info level', function () {
    expect(LogLevel::Info->color())->toBe('success');
});

it('returns gray color for debug level', function () {
    expect(LogLevel::Debug->color())->toBe('gray');
});

it('returns a heroicon string for each case', function () {
    foreach (LogLevel::cases() as $case) {
        expect($case->icon())->toStartWith('heroicon-o-');
    }
});

it('returns specific icons for each level', function () {
    expect(LogLevel::Emergency->icon())->toBe('heroicon-o-fire');
    expect(LogLevel::Alert->icon())->toBe('heroicon-o-bell-alert');
    expect(LogLevel::Critical->icon())->toBe('heroicon-o-x-circle');
    expect(LogLevel::Error->icon())->toBe('heroicon-o-exclamation-circle');
    expect(LogLevel::Warning->icon())->toBe('heroicon-o-exclamation-triangle');
    expect(LogLevel::Notice->icon())->toBe('heroicon-o-megaphone');
    expect(LogLevel::Info->icon())->toBe('heroicon-o-information-circle');
    expect(LogLevel::Debug->icon())->toBe('heroicon-o-bug-ant');
});

it('returns null for invalid string via tryFrom', function () {
    expect(LogLevel::tryFrom('INVALID'))->toBeNull();
    expect(LogLevel::tryFrom(''))->toBeNull();
});
