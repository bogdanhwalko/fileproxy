@php
    $shareUrl = $folder->share_token ? route('share.folders.show', $folder->share_token) : '';
    $shareExpiresInput = $folder->share_expires_at?->format('Y-m-d\TH:i');
    $shareViewsLabel = $folder->share_max_views
        ? $folder->share_views_count.' / '.$folder->share_max_views
        : $folder->share_views_count.' / без ліміту';
    $shareExpiresLabel = $folder->share_expires_at
        ? $folder->share_expires_at->format('d.m.Y H:i')
        : 'без дати';
    $folderColorHex = $folder->color && isset(\App\Models\FileFolder::COLOR_PALETTE[$folder->color])
        ? \App\Models\FileFolder::COLOR_PALETTE[$folder->color]
        : null;
@endphp

<details
    class="file-action-menu folder-action-menu"
    data-file-share
    data-share-url="{{ route('folders.share', $folder) }}"
    data-share-settings-url="{{ route('folders.share.update', $folder) }}"
    data-share-disable-url="{{ route('folders.share.destroy', $folder) }}"
>
    <summary class="button secondary action-menu-trigger folder-action-button">Дії</summary>

    <div class="file-action-panel folder-action-panel fa-panel">
        {{-- Compact header with folder color preview --}}
        <div class="fa-head" data-action-drag-handle>
            <div class="fa-head-title">
                <span class="fa-head-color" aria-hidden="true" @if ($folderColorHex) style="background:{{ $folderColorHex }}" @endif>
                    @if (! $folderColorHex)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                    @endif
                </span>
                <div class="fa-head-text">
                    <strong>{{ $folder->name }}</strong>
                    <span>{{ $folder->files_count ?? $folder->files()->count() }} {{ ($folder->files_count ?? 0) === 1 ? 'файл' : 'файлів' }}</span>
                </div>
            </div>
            <button class="action-panel-close" type="button" data-action-close aria-label="Закрити меню">✕</button>
        </div>

        {{-- Section: Properties (name + color + save) --}}
        <section class="fa-section">
            <header class="fa-section-head">
                <span class="fa-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Властивості
                </span>
            </header>
            <form class="fa-folder-form" action="{{ route('folders.update', $folder) }}" method="post" data-ajax-form>
                @csrf
                @method('patch')
                <label class="fa-folder-field">
                    <span>Назва</span>
                    <input class="field" type="text" name="name" value="{{ $folder->name }}" maxlength="100" required>
                </label>

                <div class="fa-folder-field">
                    <span>Колір</span>
                    <div class="folder-color-picker" role="radiogroup" aria-label="Колір папки">
                        <label class="folder-color-swatch folder-color-swatch-none" title="Без кольору">
                            <input type="radio" name="color" value="" @checked(!$folder->color)>
                            <span aria-hidden="true">⌀</span>
                        </label>
                        @foreach (\App\Models\FileFolder::COLOR_PALETTE as $colorKey => $colorHex)
                            <label class="folder-color-swatch" style="--swatch:{{ $colorHex }}" title="{{ ucfirst($colorKey) }}">
                                <input type="radio" name="color" value="{{ $colorKey }}" @checked($folder->color === $colorKey)>
                                <span aria-hidden="true"></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button class="button fa-folder-save" type="submit">Зберегти зміни</button>
            </form>
        </section>

        {{-- Section: Public link (switch toggle + URL + limits) --}}
        <section class="fa-section fa-share-section {{ $folder->share_token ? 'is-enabled' : '' }}">
            <header class="fa-section-head">
                <span class="fa-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                    Публічний доступ
                </span>
                <label class="fa-switch" title="Увімкнути / вимкнути публічний доступ">
                    <input type="checkbox" data-share-toggle @checked($folder->share_token)>
                    <span class="fa-switch-track"><span class="fa-switch-knob"></span></span>
                </label>
            </header>

            {{-- Hidden when share is off; JS toggles via [data-share-enabled]/[data-share-disabled] --}}
            <div data-share-disabled @if ($folder->share_token) hidden @endif>
                <p class="fa-share-off-hint">Створіть лінк, щоб поділитися папкою. Перемикач увімкне доступ.</p>
            </div>

            <div data-share-enabled @unless ($folder->share_token) hidden @endunless>
                <div class="fa-link-row">
                    <input class="field fa-link-input" type="text" value="{{ $shareUrl }}" data-share-link-input readonly>
                    <button type="button" class="fa-icon-btn" data-share-copy title="Копіювати лінк">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                    <a class="fa-icon-btn" href="{{ $shareUrl ?: '#' }}" target="_blank" rel="noopener" data-share-open title="Відкрити" @if (! $shareUrl) aria-disabled="true" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                </div>

                <p class="fa-usage" data-share-usage>{{ 'Переглядів: '.$shareViewsLabel.' · до: '.$shareExpiresLabel }}</p>

                <details class="fa-limits">
                    <summary>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        Ліміти доступу
                    </summary>
                    <div class="fa-limits-grid">
                        <label>
                            <span>Переглядів</span>
                            <input class="field" type="number" min="1" max="1000000" name="share_max_views" value="{{ $folder->share_max_views }}" placeholder="без ліміту" data-share-max-views>
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

        {{-- Section: Password protection (only if set) --}}
        @if ($folder->is_password_protected)
            <section class="fa-section fa-password-section">
                <header class="fa-section-head">
                    <span class="fa-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        Захист паролем
                    </span>
                    <span class="fa-section-badge fa-section-badge-amber">Захищено</span>
                </header>

                <form action="{{ route('folders.lock', $folder) }}" method="post" class="fa-password-lock-now">
                    @csrf
                    <button class="button secondary" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Заблокувати зараз
                    </button>
                </form>

                <details class="fa-collapse">
                    <summary>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        Змінити пароль
                    </summary>
                    <form action="{{ route('folders.password.set', $folder) }}" method="post" class="fa-password-form">
                        @csrf
                        <label>
                            <span>Поточний пароль</span>
                            <input class="field" type="password" name="current_password" required autocomplete="current-password">
                        </label>
                        <label>
                            <span>Новий пароль</span>
                            <input class="field" type="password" name="password" minlength="4" maxlength="128" required autocomplete="new-password">
                        </label>
                        <button class="button secondary" type="submit">Зберегти новий пароль</button>
                    </form>
                </details>

                <details class="fa-collapse">
                    <summary>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        Зняти пароль
                    </summary>
                    <form action="{{ route('folders.password.remove', $folder) }}" method="post" class="fa-password-form">
                        @csrf
                        @method('delete')
                        <label>
                            <span>Поточний пароль</span>
                            <input class="field" type="password" name="current_password" required autocomplete="current-password">
                        </label>
                        <button class="button secondary fa-btn-danger-outline" type="submit">Зняти пароль</button>
                    </form>
                </details>

                <p class="fa-section-hint">Файли в захищеній папці шифруються AES-GCM перед відправкою в Telegram.</p>
            </section>
        @endif

        {{-- Danger zone: delete --}}
        <section class="fa-section fa-danger-section">
            <header class="fa-section-head">
                <span class="fa-section-title fa-section-title-danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    Небезпечна зона
                </span>
            </header>
            <p class="fa-danger-hint">Видалення безповоротне. Записи всіх файлів папки будуть видалені.</p>
            <form action="{{ route('folders.destroy', $folder) }}" method="post" data-ajax-form>
                @csrf
                @method('delete')
                <button class="button fa-btn-danger" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    Видалити папку
                </button>
            </form>
        </section>
    </div>
</details>
