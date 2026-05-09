<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailTemplateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource;
use FinityLabs\FinMail\Settings\GeneralSettings;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

    public string $activeLocale = '';

    /**
     * Stashed translatable field values per locale, so switching locales doesn't lose work.
     *
     * @var array<string, array<string, string>>
     */
    public array $localeData = [];

    private const TRANSLATABLE_FIELDS = ['name', 'subject', 'preheader', 'body'];

    public function mount(): void
    {
        $this->activeLocale = app(GeneralSettings::class)->default_locale;

        parent::mount();
    }

    public function switchLocale(string $locale): void
    {
        // Stash current translatable fields for the old locale
        $formData = $this->form->getState();

        foreach (self::TRANSLATABLE_FIELDS as $field) {
            $this->localeData[$this->activeLocale][$field] = $formData[$field] ?? '';
        }

        // Switch to the new locale
        $this->activeLocale = $locale;

        // Restore stashed data for the new locale (or empty strings)
        $fill = [];

        foreach (self::TRANSLATABLE_FIELDS as $field) {
            $fill[$field] = $this->localeData[$locale][$field] ?? '';
        }

        $fill['active_locale'] = $locale;

        $this->form->fill(array_merge($formData, $fill));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Stash the current locale's data before building translation arrays
        foreach (self::TRANSLATABLE_FIELDS as $field) {
            $this->localeData[$this->activeLocale][$field] = $data[$field] ?? '';
        }

        unset($data['active_locale']);

        // Build Spatie translation arrays from all stashed locales
        foreach (self::TRANSLATABLE_FIELDS as $field) {
            $values = [];

            foreach ($this->localeData as $loc => $fields) {
                if (! empty($fields[$field])) {
                    $values[$loc] = $fields[$field];
                }
            }

            $data[$field] = $values;
        }

        return $data;
    }
}
