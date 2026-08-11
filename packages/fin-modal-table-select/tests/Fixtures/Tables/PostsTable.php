<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Tests\Fixtures\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('body'),
            ]);
    }
}
