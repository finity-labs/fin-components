<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Facades;

use FinityLabs\FinMail\FinMailManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \FinityLabs\FinMail\Mail\TemplateMail make(string $templateKey, ?string $locale = null)
 * @method static \FinityLabs\FinMail\Models\EmailTemplate|null findTemplate(string $key, ?string $locale = null)
 * @method static string replace(string $content, array $models = [])
 * @method static array templateKeys(?string $locale = null)
 * @method static string|null dateFormat()
 * @method static string|null dateTimeFormat()
 *
 * @see FinMailManager
 */
class FinMail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'fin-mail';
    }
}
