<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailThemeResource;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use FinityLabs\FinMail\Models\EmailTheme;
use FinityLabs\FinMail\Resources\EmailThemeResource\Schemas\EmailThemeForm;
use FinityLabs\FinMail\Resources\EmailThemeResource\Tables\EmailThemesTable;
use UnitEnum;

class EmailThemeResource extends Resource
{
    protected static ?string $model = EmailTheme::class;

    protected static ?string $slug = 'email-themes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static string|UnitEnum|null $navigationGroup = 'Email';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Themes';

    public static function form(Schema $form): Schema
    {
        return EmailThemeForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return EmailThemesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailThemes::route('/'),
            'create' => Pages\CreateEmailTheme::route('/create'),
            'edit' => Pages\EditEmailTheme::route('/{record}/edit'),
        ];
    }
}
