<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Pages;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use FinityLabs\FinCodex\FinCodexPlugin;
use FinityLabs\LinCodex\Enums\FallbackBehaviour;
use FinityLabs\LinCodex\Settings\CodexSettings;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * The Codex settings screen: languages, reading behaviour and revisions.
 *
 * FinCodexPlugin::register() puts this class on every panel that carries the
 * plugin, unless the host named its own through settingsPage(); such an
 * override extends this class, which is why the class is not final — the same
 * reason Resources\ArticleResource is not. The slug is inherited, so each
 * panel gets its own filament.{panel}.pages.help-settings route.
 *
 * Navigation placement is not the page's business: the group comes from the
 * panel's own plugin options and the sort is that panel's article-resource
 * sort plus one, so the page always files directly after Help articles and a
 * host that never set a sort keeps Filament's label ordering.
 */
class HelpSettings extends SettingsPage
{
    protected static string $settings = CodexSettings::class;

    protected static ?string $slug = 'help-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return FinCodexPlugin::get()->getNavigationGroup();
    }

    /**
     * One slot after the article resource, which reads the same single
     * navigationSort() option. The page adds one to whatever the host set and
     * stays null when the host set nothing, in which case Filament sorts by
     * label.
     */
    public static function getNavigationSort(): ?int
    {
        $sort = FinCodexPlugin::get()->getNavigationSort();

        return $sort === null ? null : $sort + 1;
    }

    public static function getNavigationLabel(): string
    {
        return (string) __('fin-codex::fin-codex.settings.navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return (string) __('fin-codex::fin-codex.settings.title');
    }

    public function getSavedNotificationTitle(): ?string
    {
        return (string) __('fin-codex::fin-codex.settings.saved');
    }

    /**
     * Three stacked sections. defaultForm() already applies columns(2) and
     * statePath('data'), so neither is repeated here.
     */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('fin-codex::fin-codex.settings.languages.section'))
                ->description(__('fin-codex::fin-codex.settings.languages.description'))
                ->columnSpanFull()
                ->schema([
                    Repeater::make('languages')
                        ->hiddenLabel()
                        ->addActionLabel(__('fin-codex::fin-codex.settings.languages.add'))
                        ->minItems(1)
                        ->columns(3)
                        ->schema([
                            TextInput::make('code')
                                ->label(__('fin-codex::fin-codex.settings.languages.code'))
                                ->required()
                                ->maxLength(10)
                                // Live so the default-language select below follows the
                                // list as it is edited, without a save.
                                ->live(onBlur: true),
                            TextInput::make('display')
                                ->label(__('fin-codex::fin-codex.settings.languages.display'))
                                ->required()
                                ->maxLength(60),
                            TextInput::make('flag-icon')
                                ->label(__('fin-codex::fin-codex.settings.languages.flag'))
                                ->maxLength(4)
                                ->helperText(__('fin-codex::fin-codex.settings.languages.flag_help')),
                        ]),
                ]),

            Section::make(__('fin-codex::fin-codex.settings.reading.section'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('default_locale')
                        ->label(__('fin-codex::fin-codex.settings.reading.default_locale'))
                        ->helperText(__('fin-codex::fin-codex.settings.reading.default_locale_help'))
                        ->options(fn (Get $get): array => self::languageOptions($get))
                        ->live()
                        ->required(),
                    Select::make('fallback')
                        ->label(__('fin-codex::fin-codex.settings.reading.fallback'))
                        // The core enum's own labels; fin-codex never redeclares them.
                        ->options(collect(FallbackBehaviour::cases())
                            ->mapWithKeys(fn (FallbackBehaviour $case): array => [$case->value => $case->label()])
                            ->all())
                        ->required(),
                ]),

            Section::make(__('fin-codex::fin-codex.settings.revisions.section'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Toggle::make('revisions_enabled')
                        ->label(__('fin-codex::fin-codex.settings.revisions.enabled'))
                        ->helperText(__('fin-codex::fin-codex.settings.revisions.enabled_help')),
                    TextInput::make('revisions_keep')
                        ->label(__('fin-codex::fin-codex.settings.revisions.keep'))
                        ->helperText(__('fin-codex::fin-codex.settings.revisions.keep_help'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(999)
                        ->required(),
                ]),
        ]);
    }

    /**
     * The enum arrives as a FallbackBehaviour instance from toArray(). The
     * Select would flatten it to its backing value on its own; doing it here
     * keeps the form state plain scalars and mirrors the save mutator, so the
     * pair reads as one round trip rather than as one half of it.
     *
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $fallback = $data['fallback'] ?? null;
        $data['fallback'] = $fallback instanceof FallbackBehaviour ? $fallback->value : $fallback;

        return $data;
    }

    /**
     * Both casts are load-bearing. Settings::fill() assigns straight onto a
     * property typed FallbackBehaviour, and SettingsMapper's enum cast throws
     * "Invalid enum" for anything that is not a BackedEnum; TextInput::numeric()
     * hands back a float, which a typed int property refuses.
     *
     * Languages need no mutation in either direction: the repeater dehydrates
     * to a clean list of {code, display, flag-icon}, which is exactly the shape
     * CodexSettings::$languages declares.
     *
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['fallback'] = FallbackBehaviour::from((int) $data['fallback']);
        $data['revisions_keep'] = (int) $data['revisions_keep'];

        return $data;
    }

    /**
     * The default-language options, read live from the languages repeater:
     * code => display, falling back to the code, with blank codes dropped so a
     * half-typed new row does not offer an empty option.
     *
     * @return array<string, string>
     */
    private static function languageOptions(Get $get): array
    {
        $languages = $get('languages');

        return collect(is_array($languages) ? $languages : [])
            ->mapWithKeys(function (mixed $row): array {
                $code = is_array($row) ? (string) ($row['code'] ?? '') : '';
                $display = is_array($row) ? (string) ($row['display'] ?? '') : '';

                return [$code => $display === '' ? $code : $display];
            })
            ->filter(fn (string $label, string $code): bool => $code !== '')
            ->all();
    }
}
