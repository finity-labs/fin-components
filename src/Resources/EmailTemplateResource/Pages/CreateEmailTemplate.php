<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailTemplateResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource;
use FinityLabs\FinMail\Settings\GeneralSettings;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

    public string $activeLocale = '';

    public function mount(): void
    {
        $this->activeLocale = app(GeneralSettings::class)->default_locale;

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
        $languages = app(GeneralSettings::class)->languages;

        return [
            ActionGroup::make(
                collect($languages)
                    ->map(
                        fn (array $lang) => Action::make("locale_{$lang['code']}")
                            ->label($lang['display'])
                            ->color($this->activeLocale === $lang['code'] ? 'primary' : 'gray')
                            ->action(function () use ($lang): void {
                                $this->activeLocale = $lang['code'];
                            })
                    )->values()->all()
            )
                ->label(fn (): string => __('fin-mail::fin-mail.template.language_label', ['locale' => strtoupper($this->activeLocale)]))
                ->icon(Heroicon::OutlinedLanguage)
                ->button(),
        ];
    }
}
