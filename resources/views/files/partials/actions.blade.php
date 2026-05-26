@php
    $shareUrl = $file->share_token ? route('share.files.show', $file->share_token) : '';
    $shareRawUrl = $file->share_token ? route('share.files.raw', $file->share_token) : '';
    $shareExpiresInput = $file->share_expires_at?->format('Y-m-d\TH:i');
    $shareViewsLabel = $file->share_max_views
        ? $file->share_views_count.' / '.$file->share_max_views
        : $file->share_views_count.' / без ліміту';
    $shareExpiresLabel = $file->share_expires_at
        ? $file->share_expires_at->format('d.m.Y H:i')
        : 'без дати';
    $currentTags = $file->relationLoaded('tags') ? $file->tags : $file->tags()->get();
    $currentTagsCsv = $currentTags->pluck('name')->implode(', ');
@endphp

<details
    class="file-action-menu"
    data-file-share
    data-share-url="{{ route('files.share', $file) }}"
    data-share-settings-url="{{ route('files.share.update', $file) }}"
    data-share-disable-url="{{ route('files.share.destroy', $file) }}"
    data-tags-url="{{ route('files.tags.update', $file) }}"
>
    <summary class="button secondary action-menu-trigger">Дії</summary>

    <div class="file-action-panel fa-panel">
        <div class="fa-head" data-action-drag-handle>
            <strong>Дії з файлом</strong>
            <button class="action-panel-close" type="button" data-action-close aria-label="Закрити меню">✕</button>
        </div>

        {{-- Quick actions: icon + label, compact row --}}
        <div class="fa-quick">
            @if ($file->is_uploaded && $file->is_previewable && ! $file->is_protected)
                <a class="fa-quick-btn fa-quick-btn-accent" href="{{ route('files.preview', $file) }}" title="Переглянути">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Переглянути</span>
                </a>
            @endif
            @php
                $editorExtensions = ['txt','md','markdown','csv','log','json','yaml','yml','ini','conf','env','xml','sql','tex','html','htm','css','scss','sass','less','svg','js','mjs','ts','jsx','tsx','vue','php','py','rb','sh','bash','zsh','go','rs','java','kt','swift','c','cpp','cc','h','hpp','cs','r','lua','pl'];
                $canEditText = $file->is_uploaded
                    && $file->is_text
                    && in_array(strtolower((string) $file->extension), $editorExtensions, true)
                    && ! $file->is_protected
                    && ! ($file->folder?->is_password_protected);
            @endphp
            @php
                $ext = strtolower((string) $file->extension);
                $canEditDoc = $canEditText && in_array($ext, ['html', 'htm'], true);
            @endphp
            @if ($canEditDoc)
                <a class="fa-quick-btn" href="{{ route('files.edit-doc', $file) }}" title="Редагувати у WYSIWYG-редакторі">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>
                    <span>Документ</span>
                </a>
            @endif
            @if ($canEditText)
                <a class="fa-quick-btn" href="{{ route('files.edit-text', $file) }}" title="Редагувати у текстовому редакторі">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    <span>{{ $canEditDoc ? 'Код' : 'Редагувати' }}</span>
                </a>
            @endif
            @if ($file->is_uploaded)
                <a class="fa-quick-btn" href="{{ route('files.download', $file) }}" title="Скачати">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Скачати</span>
                </a>
            @endif
            <form class="fa-quick-form" action="{{ route('files.destroy', $file) }}" method="post" data-ajax-form>
                @csrf
                @method('delete')
                <button class="fa-quick-btn fa-quick-btn-danger" type="submit" title="{{ $file->is_uploaded ? 'Видалити' : 'Скасувати' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    <span>{{ $file->is_uploaded ? 'Видалити' : 'Скасувати' }}</span>
                </button>
            </form>
        </div>

        @if (! $file->is_uploaded)
            <div class="file-status-note file-status-{{ $file->status }}">
                <strong>{{ $file->status_label }}</strong>
                @if ($file->is_failed && $file->upload_failure_reason)
                    <p>{{ $file->upload_failure_reason }}</p>
                @elseif ($file->is_pending)
                    <p>Файл переноситься в Telegram у фоновому режимі. Оновіть сторінку через хвилину.</p>
                @endif
            </div>
        @endif

        {{-- Tags: chip input with autosave --}}
        <section class="fa-section" data-file-tags-container>
            <header class="fa-section-head">
                <span class="fa-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    Теги
                </span>
                <span class="fa-section-state" data-action-tags-status></span>
            </header>
            <div class="fa-chip-field" data-action-tags-chips>
                <input
                    type="text"
                    class="fa-chip-typing"
                    data-action-tags-typing
                    maxlength="64"
                    placeholder="новий тег + Enter"
                    autocomplete="off"
                >
            </div>
            <input type="hidden" data-action-tags-input value="{{ $currentTagsCsv }}">
            <p class="fa-section-message" data-action-tags-message></p>
        </section>

        @if ($file->is_uploaded)
        {{-- Public link: switch + tabs + collapsible limits --}}
        <section class="fa-section fa-share-section {{ $file->share_token ? 'is-enabled' : '' }}">
            <header class="fa-section-head">
                <span class="fa-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    Публічний лінк
                </span>
                <label class="fa-switch" title="Увімкнути / вимкнути публічний доступ">
                    <input type="checkbox" data-share-toggle @checked($file->share_token)>
                    <span class="fa-switch-track"><span class="fa-switch-knob"></span></span>
                </label>
            </header>

            <div class="fa-share-body" data-share-enabled @unless ($file->share_token) hidden @endunless>
                {{-- URL tabs --}}
                <div class="fa-tabs" role="tablist">
                    <button type="button" class="fa-tab is-active" data-fa-tab="page" role="tab" aria-selected="true">Сторінка</button>
                    <button type="button" class="fa-tab" data-fa-tab="raw" role="tab" aria-selected="false">Прямий</button>
                </div>

                <div class="fa-tab-panel" data-fa-tab-panel="page">
                    <div class="fa-link-row">
                        <input class="field fa-link-input" type="text" value="{{ $shareUrl }}" data-share-link-input readonly>
                        <button type="button" class="fa-icon-btn" data-share-copy title="Копіювати">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                        <a class="fa-icon-btn" href="{{ $shareUrl ?: '#' }}" target="_blank" rel="noopener" data-share-open title="Відкрити" @if (! $shareUrl) aria-disabled="true" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    </div>
                </div>

                <div class="fa-tab-panel" data-fa-tab-panel="raw" hidden>
                    <div class="fa-link-row">
                        <input class="field fa-link-input" type="text" value="{{ $shareRawUrl }}" data-share-raw-link-input readonly>
                        <button type="button" class="fa-icon-btn" data-share-raw-copy title="Копіювати">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                        <a class="fa-icon-btn" href="{{ $shareRawUrl ?: '#' }}" target="_blank" rel="noopener" data-share-raw-open title="Відкрити" @if (! $shareRawUrl) aria-disabled="true" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    </div>
                    <p class="fa-link-hint">Сирий файл без HTML-обгортки — для embed'ів, месенджерів.</p>
                </div>

                {{-- Usage stats --}}
                <p class="fa-usage" data-share-usage>{{ 'Переглядів: '.$shareViewsLabel.' · до: '.$shareExpiresLabel }}</p>

                {{-- Collapsible limits --}}
                <details class="fa-limits">
                    <summary>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        Ліміти доступу
                    </summary>
                    <div class="fa-limits-grid">
                        <label>
                            <span>Переглядів</span>
                            <input class="field" type="number" min="1" max="1000000" name="share_max_views" value="{{ $file->share_max_views }}" placeholder="без ліміту" data-share-max-views>
                        </label>
                        <label>
                            <span>Доступний до</span>
                            <input class="field" type="datetime-local" name="share_expires_at" value="{{ $shareExpiresInput }}" data-share-expires-at>
                        </label>
                    </div>
                    <button class="button secondary fa-limits-save" type="button" data-share-save>Зберегти ліміти</button>
                </details>
            </div>

            <p class="fa-section-message" data-share-message></p>
        </section>
        @endif
    </div>
</details>
