<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailThemeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use FinityLabs\FinMail\Resources\EmailThemeResource\EmailThemeResource;

class EditEmailTheme extends EditRecord
{
    protected static string $resource = EmailThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (): void {
                    $this->record->templates()->update(['email_theme_id' => null]);
                }),
        ];
    }
}
