<?php

declare(strict_types=1);

namespace FinityLabs\FinMail;

use FinityLabs\FinMail\Helpers\TokenReplacer;
use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Models\EmailTemplate;

/**
 * Manager class backing the FinMail facade.
 *
 * Provides a convenient API:
 *   FinMail::make('invoice-sent')->models([...])->...
 *   FinMail::findTemplate('invoice-sent')
 *   FinMail::replace('Hello {{ user.name }}', ['user' => $user])
 */
class FinMailManager
{
    public function make(string $templateKey, ?string $locale = null): TemplateMail
    {
        return TemplateMail::make($templateKey, $locale);
    }

    public function findTemplate(string $key, ?string $locale = null): ?EmailTemplate
    {
        return EmailTemplate::findByKey($key, $locale);
    }

    public function replace(string $content, array $models = []): string
    {
        return app(TokenReplacer::class)->replace($content, $models);
    }

    /**
     * @return array<int, string>
     */
    public function templateKeys(): array
    {
        return EmailTemplate::active()
            ->pluck('key')
            ->all();
    }
}
