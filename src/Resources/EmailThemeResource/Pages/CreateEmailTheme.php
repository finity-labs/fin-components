<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailThemeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use FinityLabs\FinMail\Resources\EmailThemeResource\EmailThemeResource;

class CreateEmailTheme extends CreateRecord
{
    protected static string $resource = EmailThemeResource::class;
}
