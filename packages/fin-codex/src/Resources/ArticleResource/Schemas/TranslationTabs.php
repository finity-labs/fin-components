<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use FinityLabs\FinCodex\Editor\SlugRules;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Settings\CodexSettings;
use Illuminate\Database\QueryException;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

/**
 * One tab per configured language, in settings order, labelled with the flag
 * and the display name.
 *
 * Translations are form state keyed by locale ("translations.de.title"), not
 * a relationship repeater: the writer, not the form, decides that an emptied
 * non-default tab means "delete this translation", and the whole save then
 * fits in one transaction. Only the default-language tab is required — an
 * article exists in its default language and is translated later.
 *
 * The body is a MarkdownEditor, never a WYSIWYG: article bodies round-trip
 * to Markdown files, and a rich editor would smuggle HTML into them. Uploads,
 * missing/outdated badges and the copy-from-default action arrive in 05-06;
 * the HTML read-only body in 05-07.
 *
 * Tabs::livewireProperty() keeps the active tab on the page itself, so the
 * server knows which language the admin is looking at (05-07's preview reads
 * it) and a locale key survives a round trip.
 */
final class TranslationTabs
{
    public static function make(?Article $record): Tabs
    {
        $languages = self::languages();
        $default = $languages['default'];

        $tabs = [];

        foreach ($languages['languages'] as $language) {
            $code = $language['code'];
            $label = trim(self::flag($language['flag-icon']).' '.$language['display']);

            $tabs[$code] = Tab::make($label)->schema(self::fields($code, $default, $record));
        }

        return Tabs::make('translations')
            ->livewireProperty('activeLocale')
            ->tabs($tabs);
    }

    /**
     * The configured languages and the default locale. Read from settings,
     * falling back to the packaged defaults when the settings row is not
     * there yet (a fresh install before Phase 6's settings page saves one) or
     * the table does not exist.
     *
     * @return array{languages: list<array{code: string, display: string, 'flag-icon': string}>, default: string}
     */
    public static function languages(): array
    {
        try {
            $settings = app(CodexSettings::class);

            /** @var list<array{code: string, display: string, 'flag-icon': string}> $languages */
            $languages = array_values($settings->languages);

            return ['languages' => $languages, 'default' => $settings->default_locale];
        } catch (MissingSettings|QueryException) {
            $defaults = CodexSettings::defaults();

            /** @var list<array{code: string, display: string, 'flag-icon': string}> $languages */
            $languages = array_values((array) $defaults['languages']);

            return ['languages' => $languages, 'default' => (string) $defaults['default_locale']];
        }
    }

    /**
     * The regional-indicator emoji for a two-letter flag-icon code ("de" is
     * U+1F1E9 U+1F1EA). Nothing in the stack draws flag images, and an emoji
     * needs no asset; anything that is not two ASCII letters renders nothing.
     */
    public static function flag(string $code): string
    {
        if (preg_match('/^[a-z]{2}$/', $code) !== 1) {
            return '';
        }

        return (string) mb_chr(0x1F1E6 + ord($code[0]) - ord('a'), 'UTF-8')
            .(string) mb_chr(0x1F1E6 + ord($code[1]) - ord('a'), 'UTF-8');
    }

    /**
     * Title, excerpt and body of one locale. On create, typing in the
     * default-language title suggests the slug; on edit the record already
     * has one and the title never touches it.
     *
     * @return list<Component>
     */
    private static function fields(string $code, string $default, ?Article $record): array
    {
        $isDefault = $code === $default;

        $title = TextInput::make("translations.{$code}.title")
            ->label(__('fin-codex::fin-codex.editor.form.title'))
            ->required($isDefault)
            ->maxLength(255)
            ->live(onBlur: true);

        if ($isDefault && $record === null) {
            $title = $title->afterStateUpdated(self::suggestSlug());
        }

        return [
            $title,
            Textarea::make("translations.{$code}.excerpt")
                ->label(__('fin-codex::fin-codex.editor.form.excerpt'))
                ->rows(3),
            MarkdownEditor::make("translations.{$code}.body")
                ->label(__('fin-codex::fin-codex.editor.form.body'))
                ->required($isDefault),
        ];
    }

    /**
     * Write the slug suggested by the title, but only while the admin has
     * not typed one of their own: slug_suggested remembers what was last
     * suggested, so a slug the admin edited is never overwritten. Get and Set
     * resolve against the form root here because neither Tabs nor Tab carries
     * a state path.
     */
    private static function suggestSlug(): callable
    {
        return static function (Set $set, Get $get, ?string $state): void {
            $current = (string) $get('slug');
            $suggested = (string) $get('slug_suggested');

            if ($current !== '' && $current !== $suggested) {
                return;
            }

            $slug = SlugRules::suggest($state);

            $set('slug', $slug);
            $set('slug_suggested', $slug);
        };
    }
}
