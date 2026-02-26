<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailTemplateResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource;
use FinityLabs\FinMail\Settings\MailSettings;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

    public string $activeLocale = '';

    public function mount(): void
    {
        $this->activeLocale = app(MailSettings::class)->default_locale;

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach (['name', 'subject', 'preheader', 'body'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = [$this->activeLocale => $data[$field]];
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        $languages = app(MailSettings::class)->languages;

        return [
            ActionGroup::make(
                collect($languages)
                    ->map(
                        fn (array $lang, string $code) => Action::make("locale_{$code}")
                            ->label($lang['display'])
                            ->color($this->activeLocale === $code ? 'primary' : 'gray')
                            ->action(function () use ($code): void {
                                $this->activeLocale = $code;
                            })
                    )->values()->all()
            )
                ->label(fn (): string => 'Language: '.strtoupper($this->activeLocale))
                ->icon('heroicon-o-language')
                ->button(),
        ];
    }
}
