<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailThemeResource;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use FinityLabs\FinMail\Enums\NavigationGroup;
use FinityLabs\FinMail\Models\EmailTheme;
use FinityLabs\FinMail\Resources\EmailThemeResource\Schemas\EmailThemeForm;
use FinityLabs\FinMail\Resources\EmailThemeResource\Schemas\EmailThemeInfolist;
use FinityLabs\FinMail\Resources\EmailThemeResource\Tables\EmailThemesTable;
use UnitEnum;

class EmailThemeResource extends Resource
{
    protected static ?string $model = EmailTheme::class;

    protected static ?string $slug = 'email-themes';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Email;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('fin-mail::fin-mail.navigation.themes');
    }

    public static function form(Schema $schema): Schema
    {
        return EmailThemeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmailThemeInfolist::configure($schema);
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
            'view' => Pages\ViewEmailTheme::route('/{record}'),
            'edit' => Pages\EditEmailTheme::route('/{record}/edit'),
        ];
    }
}
