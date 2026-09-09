<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Tests\Fixtures\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * A configuration for standaloneRecords() mode: columns only, no query —
 * the component supplies the records() data source itself.
 */
class RoutesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('route')->searchable(),
                TextColumn::make('uri'),
                TextColumn::make('panel')->badge()->color('gray'),
            ]);
    }
}
