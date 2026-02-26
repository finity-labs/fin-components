<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailTemplateResource;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Resources\EmailTemplateResource\Schemas\EmailTemplateForm;
use FinityLabs\FinMail\Resources\EmailTemplateResource\Tables\EmailTemplatesTable;
use UnitEnum;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $slug = 'email-templates';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'Email';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Templates';
    }

    public static function form(Schema $form): Schema
    {
        return EmailTemplateForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return EmailTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
            'compose' => Pages\ComposeEmail::route('/{record}/compose'),
        ];
    }
}
