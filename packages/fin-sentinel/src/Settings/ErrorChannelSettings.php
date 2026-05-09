<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Settings;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelSettings\Settings;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ErrorChannelSettings extends Settings
{
    public array $error_recipients = [];

    public bool $error_enabled = true;

    public int $error_throttle_minutes = 15;

    public bool $error_throttle_exceptions = true;

    public bool $error_throttle_log_messages = true;

    public array $ignored_exceptions = [
        NotFoundHttpException::class,
        ValidationException::class,
        AuthenticationException::class,
    ];

    public static function group(): string
    {
        return 'fin-sentinel';
    }
}
