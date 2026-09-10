<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use FinityLabs\FinCodex\Auth\ArticleAbility;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Translation\ArticleTranslator;

/**
 * Copy from default's twin, with a translation in the middle.
 *
 * Same place in the tab, same gray button, same three fields: title, excerpt
 * and body are read off the *unsaved* default-language tab and written to this
 * one. The admin has usually just written the English text; reading the stored
 * row would translate whatever was saved last time and ignore the edit in
 * front of them. Nothing is saved here either - no translation row, no
 * revision, no marker on the tab - and the tab the admin was on stays open,
 * because the action never touches activeLocale.
 *
 * Three gates, and Filament ANDs them:
 *
 * - availability. The schema asks AiAvailabilityCheck once per build and hands
 *   the answer down as a bool, so all four unavailable states (no SDK, an
 *   unseeded settings group, the toggle off, no key) take the button away
 *   entirely. The Help settings page is where the admin reads why.
 * - the ability. update for an article being edited, create on the create
 *   page, both through ArticleAbility so a host policy that stops at the five
 *   standard methods still answers. authorize() takes a Closure and must keep
 *   taking one: Filament unshifts the action's own record as the gate subject,
 *   so the string form is gated against whatever record the action happens to
 *   carry - Phase 8's finding, and the reason no gate in this package is ever
 *   handed a plain ability name. The ?Article is nullable because the create
 *   page has no record yet.
 * - a source worth translating. While the default tab's live title or body is
 *   blank the button is there but disabled, with a tooltip naming the language
 *   to fill in first. Filament leaves the HTML disabled attribute off when a
 *   tooltip is present so the hint is reachable on hover, and refuses to mount
 *   or call the action on the server either way.
 *
 * An HTML article gets no button at all: its body is a read-only textarea
 * until the convert action turns it into Markdown, so there would be nothing
 * to write the translation into.
 */
final class TranslateWithAiAction
{
    public static function make(string $code, string $default, string $display, string $defaultDisplay, bool $available): Action
    {
        return Action::make('translate_with_ai')
            ->label(__('fin-codex::fin-codex.editor.translate.label'))
            ->icon(Heroicon::OutlinedSparkles)
            ->color('gray')
            ->extraAttributes(['data-fin-codex-translate' => $code])
            ->visible($available && $code !== $default)
            ->authorize(static fn (?Article $record): bool => $record instanceof Article
                ? ArticleAbility::allows('update', $record)
                : ArticleAbility::allows('create'))
            ->disabled(static fn (Get $get): bool => self::sourceBlank($get, $default))
            ->tooltip(static fn (Get $get): ?string => self::sourceBlank($get, $default)
                ? (string) __('fin-codex::fin-codex.editor.translate.empty_source', ['language' => $defaultDisplay])
                : null)
            ->action(static function (Get $get, Set $set) use ($code, $default, $display): void {
                $excerpt = $get("translations.{$default}.excerpt");

                $result = app(ArticleTranslator::class)->translateText(
                    (string) $get("translations.{$default}.title"),
                    is_string($excerpt) && $excerpt !== '' ? $excerpt : null,
                    (string) $get("translations.{$default}.body"),
                    $code,
                    $default,
                );

                if (! $result->ok) {
                    return;
                }

                /*
                 * All three, always. A partial fill would leave the tab half in
                 * one language and half in another; a null excerpt is the
                 * translator's never-invent rule reporting a blank source one,
                 * and clearing the field is the honest answer to it.
                 */
                $set("translations.{$code}.title", $result->title);
                $set("translations.{$code}.excerpt", $result->excerpt);
                $set("translations.{$code}.body", $result->body);

                Notification::make()
                    ->success()
                    ->title(__('fin-codex::fin-codex.editor.translate.done', ['language' => $display]))
                    ->send();
            });
    }

    /**
     * Whether the default tab is missing something to translate, read from
     * live form state the way the Missing badge is. Get resolves against the
     * form root: neither Tabs nor Tab nor the actions row carries a state path
     * of its own.
     */
    private static function sourceBlank(Get $get, string $default): bool
    {
        return blank($get("translations.{$default}.title")) || blank($get("translations.{$default}.body"));
    }
}
