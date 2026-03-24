<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Enums;

enum LogLevel: string
{
    case Emergency = 'EMERGENCY';
    case Alert = 'ALERT';
    case Critical = 'CRITICAL';
    case Error = 'ERROR';
    case Warning = 'WARNING';
    case Notice = 'NOTICE';
    case Info = 'INFO';
    case Debug = 'DEBUG';

    public function color(): string
    {
        return match ($this) {
            self::Emergency, self::Alert, self::Critical, self::Error => 'danger',
            self::Warning => 'warning',
            self::Notice => 'info',
            self::Info => 'success',
            self::Debug => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Emergency => 'heroicon-o-fire',
            self::Alert => 'heroicon-o-bell-alert',
            self::Critical => 'heroicon-o-x-circle',
            self::Error => 'heroicon-o-exclamation-circle',
            self::Warning => 'heroicon-o-exclamation-triangle',
            self::Notice => 'heroicon-o-megaphone',
            self::Info => 'heroicon-o-information-circle',
            self::Debug => 'heroicon-o-bug-ant',
        };
    }
}
