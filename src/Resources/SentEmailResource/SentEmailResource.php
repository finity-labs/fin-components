<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\SentEmailResource;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use FinityLabs\FinMail\Models\SentEmail;
use FinityLabs\FinMail\Resources\SentEmailResource\Tables\SentEmailsTable;
use UnitEnum;

class SentEmailResource extends Resource
{
    protected static ?string $model = SentEmail::class;

    protected static ?string $slug = 'sent-emails';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Email';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Sent Emails';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return SentEmailsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSentEmails::route('/'),
        ];
    }
}
