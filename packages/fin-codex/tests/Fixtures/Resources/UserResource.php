<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Tests\Fixtures\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use FinityLabs\FinCodex\Help\HasHelp;
use FinityLabs\FinCodex\Help\WithHelp;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource\Pages\CreateUser;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource\Pages\EditUser;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource\Pages\ListUsers;
use FinityLabs\FinCodex\Tests\Fixtures\User;

/**
 * The one resource of the harness. In Phase 3 its list, create and edit
 * pages must hand lin-codex the resource class as the page identity. In
 * Phase 4 it declares help per panel: users then user-roles everywhere,
 * except on staff where staff-users leads and user-roles is absent.
 */
final class UserResource extends Resource implements HasHelp
{
    use WithHelp;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    /** @var array<string, list<string>> */
    protected static array $helpArticles = ['*' => ['users', 'user-roles'], 'staff' => ['staff-users', 'users']];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name'),
        ]);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
