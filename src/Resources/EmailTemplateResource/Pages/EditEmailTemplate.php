<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailTemplateResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource;
use FinityLabs\FinMail\Settings\MailSettings;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    public string $activeLocale = '';

    public function mount(int|string $record): void
    {
        $this->activeLocale = app(MailSettings::class)->default_locale;

        parent::mount($record);
    }

    protected function fillForm(): void
    {
        $this->record->setLocale($this->activeLocale);

        parent::fillForm();
    }

    /**
     * Convert translatable fields from full translations arrays to the active locale's value.
     *
     * attributesToArray() returns {'hu': '<p>...</p>'} for translatable fields,
     * but Filament form components (especially RichEditor/Tiptap) expect a string.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($this->record->translatable as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data[$key] = $data[$key][$this->activeLocale]
                    ?? $data[$key][array_key_first($data[$key])]
                    ?? '';
            }
        }

        return $data;
    }

    public function switchLocale(string $locale): void
    {
        $this->activeLocale = $locale;
        $this->record->setLocale($locale);
        $this->fillForm();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->record->setLocale($this->activeLocale);

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
                            ->action(fn () => $this->switchLocale($code))
                    )->values()->all()
            )
                ->label(fn (): string => 'Language: '.strtoupper($this->activeLocale))
                ->icon('heroicon-o-language')
                ->button(),

            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->modal()
                ->modalHeading(fn (): string => "Preview: {$this->record->name}")
                ->modalContent(fn () => view('fin-mail::components.email-preview', [
                    'html' => $this->record->body,
                ]))
                ->modalWidth('4xl')
                ->modalSubmitAction(false),

            Action::make('compose')
                ->label('Compose Email')
                ->icon('heroicon-o-paper-airplane')
                ->url(fn (): string => static::getResource()::getUrl('compose', ['record' => $this->record])),

            Action::make('version_history')
                ->label('Version History')
                ->icon('heroicon-o-clock')
                ->modal()
                ->modalHeading('Version History')
                ->modalContent(fn () => view('fin-mail::components.version-history', [
                    'versions' => $this->record->versions()->with('createdBy')->latest('version')->limit(20)->get(),
                ]))
                ->modalWidth('3xl')
                ->modalSubmitAction(false)
                ->visible(fn (): bool => (bool) config('fin-mail.versioning.enabled')),

            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        if (config('fin-mail.versioning.enabled') && $this->record->exists) {
            $this->record->saveVersion(auth()->id());
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        $notification = Notification::make()
            ->title('Template saved');

        if (config('fin-mail.versioning.enabled')) {
            $notification->body('A version snapshot was saved automatically.');
        }

        return $notification->success();
    }
}
