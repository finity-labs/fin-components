<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \FinityLabs\FinMail\Mail\TemplateMail make(string $templateKey, ?string $locale = null)
 * @method static \FinityLabs\FinMail\Models\EmailTemplate|null findTemplate(string $key, ?string $locale = null)
 * @method static string replace(string $content, array $models = [])
 * @method static array templateKeys(?string $locale = null)
 *
 * @see \FinityLabs\FinMail\FinMailManager
 */
class FinMail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'fin-mail';
    }
}
