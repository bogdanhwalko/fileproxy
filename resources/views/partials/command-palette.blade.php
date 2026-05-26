@auth
<div class="fp-palette"
    data-palette
    data-palette-search-url="{{ route('palette.search') }}"
    data-palette-files-url="{{ route('files.index') }}"
    data-palette-stats-url="{{ route('stats.index') }}"
    data-palette-tg-url="{{ route('telegram-settings.index') }}"
    hidden
>
    <div class="fp-palette-backdrop" data-palette-close></div>
    <div class="fp-palette-modal" role="dialog" aria-modal="true" aria-label="Командна палітра">
        <div class="fp-palette-input-wrap">
            <svg class="fp-palette-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text"
                class="fp-palette-input"
                placeholder="Пошук файлів, папок, тегів — або дія..."
                data-palette-input
                spellcheck="false"
                autocomplete="off">
            <kbd class="fp-palette-esc" data-palette-close>Esc</kbd>
        </div>
        <div class="fp-palette-results" data-palette-results>
            <p class="fp-palette-hint" data-palette-hint>Почніть вводити, щоб шукати у вашому сховищі.</p>
            <p class="fp-palette-empty" data-palette-empty hidden>Нічого не знайдено</p>
        </div>
        <div class="fp-palette-footer">
            <span><kbd>↑</kbd><kbd>↓</kbd> навігація</span>
            <span><kbd>↵</kbd> відкрити</span>
            <span><kbd>Esc</kbd> закрити</span>
        </div>
    </div>
</div>

@push('scripts')
    <script src="@vasset('js/command-palette.js')" defer></script>
@endpush
@endauth
