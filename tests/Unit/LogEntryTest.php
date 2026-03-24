<?php

declare(strict_types=1);

use FinityLabs\FinSentinel\Enums\LogLevel;
use FinityLabs\FinSentinel\Support\LogEntry;

function makeLogEntry(array $overrides = []): LogEntry
{
    return new LogEntry(
        timestamp: $overrides['timestamp'] ?? '2026-01-15 10:30:00',
        environment: $overrides['environment'] ?? 'production',
        level: $overrides['level'] ?? LogLevel::Error,
        message: $overrides['message'] ?? 'Test error message',
        stackTrace: array_key_exists('stackTrace', $overrides) ? $overrides['stackTrace'] : null,
        startLine: $overrides['startLine'] ?? 1,
    );
}

it('returns first 3 lines of multi-line message for preview', function () {
    $entry = makeLogEntry([
        'message' => "Line one\nLine two\nLine three\nLine four\nLine five",
    ]);

    expect($entry->preview(3))->toBe("Line one\nLine two\nLine three");
});

it('returns single-line message unchanged for preview', function () {
    $entry = makeLogEntry(['message' => 'Single line message']);

    expect($entry->preview())->toBe('Single line message');
});

it('returns fewer lines when message is shorter than requested preview', function () {
    $entry = makeLogEntry(['message' => "Line one\nLine two"]);

    expect($entry->preview(5))->toBe("Line one\nLine two");
});

it('returns true for hasStackTrace when stackTrace is non-null non-empty', function () {
    $entry = makeLogEntry(['stackTrace' => '#0 /app/index.php(10): main()']);

    expect($entry->hasStackTrace())->toBeTrue();
});

it('returns false for hasStackTrace when stackTrace is null', function () {
    $entry = makeLogEntry(['stackTrace' => null]);

    expect($entry->hasStackTrace())->toBeFalse();
});

it('returns false for hasStackTrace when stackTrace is empty string', function () {
    $entry = makeLogEntry(['stackTrace' => '']);

    expect($entry->hasStackTrace())->toBeFalse();
});

it('returns message alone for fullText when no stack trace', function () {
    $entry = makeLogEntry(['message' => 'Error occurred', 'stackTrace' => null]);

    expect($entry->fullText())->toBe('Error occurred');
});

it('returns message plus stack trace for fullText when present', function () {
    $entry = makeLogEntry([
        'message' => 'Error occurred',
        'stackTrace' => '#0 /app/index.php(10): main()',
    ]);

    expect($entry->fullText())->toBe("Error occurred\n#0 /app/index.php(10): main()");
});

it('returns correct structure from toArray', function () {
    $entry = makeLogEntry([
        'timestamp' => '2026-01-15 10:30:00',
        'environment' => 'production',
        'level' => LogLevel::Error,
        'message' => "Error line 1\nError line 2\nError line 3\nError line 4",
        'stackTrace' => '#0 /app/index.php(10): main()',
        'startLine' => 42,
    ]);

    $array = $entry->toArray();

    expect($array)->toBe([
        'timestamp' => '2026-01-15 10:30:00',
        'environment' => 'production',
        'level' => 'ERROR',
        'level_color' => 'danger',
        'level_icon' => 'heroicon-o-exclamation-circle',
        'message' => "Error line 1\nError line 2\nError line 3\nError line 4",
        'stack_trace' => '#0 /app/index.php(10): main()',
        'preview' => "Error line 1\nError line 2\nError line 3",
        'has_stack_trace' => true,
        'start_line' => 42,
    ]);
});

it('includes has_stack_trace as false when no stack trace in toArray', function () {
    $entry = makeLogEntry(['stackTrace' => null]);

    $array = $entry->toArray();

    expect($array['has_stack_trace'])->toBeFalse();
    expect($array['stack_trace'])->toBeNull();
});
