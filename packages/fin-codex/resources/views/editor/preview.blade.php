{{-- The preview slide-over's body. The html has already been through lin-codex's renderer, which is the sanitiser too, so it is echoed raw; the raw article body never is. The wrapper follows the panel's Alpine theme store exactly like the drawer (a light ancestor opts the core stylesheet out of its prefers-color-scheme rule; the static class covers a panel without dark mode before Alpine runs). .codex-root is what the stylesheet already in the panel head paints, and the body sits in .codex-article__body with its language, as it does in the drawer and the core partial: that is the class every article rule of the stylesheet hangs on — headings, paragraphs, lists, code, quotes, images. --}}
<div @if (! $hasDarkMode) class="light" @endif
     x-data
     x-bind:class="{ light: $store.theme === 'light' }">
    <div class="codex-root" data-fin-codex-preview>
        <article class="codex-article">
            <div class="codex-article__body" lang="{{ $locale }}">{!! $html !!}</div>
        </article>
    </div>
</div>
