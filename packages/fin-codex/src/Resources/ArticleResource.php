<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use FinityLabs\FinCodex\FinCodexPlugin;
use FinityLabs\FinCodex\Resources\ArticleResource\Pages\CreateArticle;
use FinityLabs\FinCodex\Resources\ArticleResource\Pages\EditArticle;
use FinityLabs\FinCodex\Resources\ArticleResource\Pages\ListArticles;
use FinityLabs\FinCodex\Resources\ArticleResource\Schemas\ArticleForm;
use FinityLabs\FinCodex\Resources\ArticleResource\Tables\ArticlesTable;
use FinityLabs\LinCodex\Models\Article;
use UnitEnum;

/**
 * The help article editor: lin-codex's Article model as a Filament resource,
 * mounted at /{panel-path}/help-articles.
 *
 * FinCodexPlugin::register() puts this class on every panel that carries the
 * plugin, unless the host named its own through articleResource(); such an
 * override extends this class, which is why the class is not final. Its pages
 * stay this resource's pages (their $resource points here) and the slug is
 * inherited, so the routes and the generated URLs line up; a host that wants
 * different pages overrides getPages() as well.
 *
 * Navigation placement is not the resource's business: the group and the sort
 * come from the panel's own plugin options, read at navigation time through
 * FinCodexPlugin::get(), so two panels can file the editor differently.
 *
 * Every write the pages perform is delegated to Editor\ArticleWriter, the one
 * transactional write path, so nothing here touches the model directly.
 */
class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $slug = 'help-articles';

    protected static ?string $recordTitleAttribute = 'slug';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return FinCodexPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return FinCodexPlugin::get()->getNavigationSort();
    }

    public static function getModelLabel(): string
    {
        return (string) __('fin-codex::fin-codex.editor.article');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('fin-codex::fin-codex.editor.articles');
    }

    public static function getNavigationLabel(): string
    {
        return (string) __('fin-codex::fin-codex.editor.navigation');
    }

    public static function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
