<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Settings;

use Spatie\LaravelSettings\Settings;

class ErrorChannelSettings extends Settings
{
    public array $error_recipients = [];

    public bool $error_enabled = true;

    public int $error_throttle_minutes = 15;

    public array $ignored_exceptions = [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
    ];

    public static function group(): string
    {
        return 'fin-sentinel';
    }
}
