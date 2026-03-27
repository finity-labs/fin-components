<?php

declare(strict_types=1);

use Filament\Support\Icons\Heroicon;
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
    expect(LogLevel::Emergency->getColor())->toBe('danger');
    expect(LogLevel::Alert->getColor())->toBe('danger');
    expect(LogLevel::Critical->getColor())->toBe('danger');
    expect(LogLevel::Error->getColor())->toBe('danger');
});

it('returns warning color for warning level', function () {
    expect(LogLevel::Warning->getColor())->toBe('warning');
});

it('returns info color for notice level', function () {
    expect(LogLevel::Notice->getColor())->toBe('info');
});

it('returns success color for info level', function () {
    expect(LogLevel::Info->getColor())->toBe('success');
});

it('returns gray color for debug level', function () {
    expect(LogLevel::Debug->getColor())->toBe('gray');
});

it('returns a BackedEnum icon for each case', function () {
    foreach (LogLevel::cases() as $case) {
        expect($case->getIcon())->toBeInstanceOf(BackedEnum::class);
    }
});

it('returns specific icons for each level', function () {
    expect(LogLevel::Emergency->getIcon())->toBe(Heroicon::OutlinedFire);
    expect(LogLevel::Alert->getIcon())->toBe(Heroicon::OutlinedBellAlert);
    expect(LogLevel::Critical->getIcon())->toBe(Heroicon::OutlinedXCircle);
    expect(LogLevel::Error->getIcon())->toBe(Heroicon::OutlinedExclamationCircle);
    expect(LogLevel::Warning->getIcon())->toBe(Heroicon::OutlinedExclamationTriangle);
    expect(LogLevel::Notice->getIcon())->toBe(Heroicon::OutlinedMegaphone);
    expect(LogLevel::Info->getIcon())->toBe(Heroicon::OutlinedInformationCircle);
    expect(LogLevel::Debug->getIcon())->toBe(Heroicon::OutlinedBugAnt);
});

it('returns null for invalid string via tryFrom', function () {
    expect(LogLevel::tryFrom('INVALID'))->toBeNull();
    expect(LogLevel::tryFrom(''))->toBeNull();
});
