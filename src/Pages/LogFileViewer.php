<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use FinityLabs\FinSentinel\Enums\LogLevel;
use FinityLabs\FinSentinel\Services\LogEntryParser;
use Illuminate\Pagination\LengthAwarePaginator;

class LogFileViewer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'sentinel/logs/{file}';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'fin-sentinel::log-viewer.viewer';

    public ?string $file = null;

    public function mount(string $file): void
    {
        $this->file = $file;

        $logsPath = storage_path('logs');
        $fullPath = $logsPath . DIRECTORY_SEPARATOR . $file;
        $realPath = realpath($fullPath);
        $realLogsPath = realpath($logsPath);

        if (
            $realPath === false
            || $realLogsPath === false
            || ! str_starts_with($realPath, $realLogsPath)
            || ! is_file($realPath)
        ) {
            $this->redirect(LogFileList::getUrl());

            return;
        }
    }

    public function getTitle(): string
    {
        return basename((string) $this->file);
    }

    public function getHeading(): string
    {
        return basename((string) $this->file);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (
                int $page,
                int $recordsPerPage,
                ?string $sortColumn,
                ?string $sortDirection,
                ?string $search,
                array $filters,
            ): LengthAwarePaginator {
                $levelFilter = $filters['level']['values'] ?? [];

                return app(LogEntryParser::class)->getEntries(
                    relativePath: (string) $this->file,
                    page: $page,
                    perPage: $recordsPerPage,
                    sortDirection: $sortDirection ?? 'desc',
                    levelFilter: $levelFilter,
                    search: $search,
                );
            })
            ->recordKey('start_line')
            ->defaultPaginationPageOption(50)
            ->defaultSort('start_line', 'desc')
            ->columns([
                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color(fn (string $state): string => LogLevel::from($state)->color())
                    ->sortable(false),

                TextColumn::make('timestamp')
                    ->label('Timestamp')
                    ->sortable(false),

                TextColumn::make('preview')
                    ->label('Message')
                    ->wrap()
                    ->lineClamp(3)
                    ->sortable(false),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label(__('fin-sentinel::fin-sentinel.log_level_filter'))
                    ->multiple()
                    ->options(
                        collect(LogLevel::cases())
                            ->mapWithKeys(fn (LogLevel $level) => [$level->value => $level->value])
                            ->all()
                    ),
            ])
            ->searchable()
            ->actions([
                Action::make('viewEntry')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (array $record): string => $record['level'] . ' - ' . $record['timestamp'])
                    ->modalContent(fn (array $record) => view('fin-sentinel::log-viewer.entry-detail', ['entry' => $record]))
                    ->modalWidth(Width::SevenExtraLarge)
                    ->slideOver()
                    ->modalSubmitAction(false),
            ]);
    }
}
