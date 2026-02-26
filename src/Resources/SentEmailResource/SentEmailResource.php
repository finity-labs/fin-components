<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\SentEmailResource;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use FinityLabs\FinMail\Enums\NavigationGroup;
use FinityLabs\FinMail\Models\SentEmail;
use FinityLabs\FinMail\Resources\SentEmailResource\Tables\SentEmailsTable;
use UnitEnum;

class SentEmailResource extends Resource
{
    protected static ?string $model = SentEmail::class;

    protected static ?string $slug = 'sent-emails';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Email;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('fin-mail::fin-mail.navigation.sent-emails');
    }

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
