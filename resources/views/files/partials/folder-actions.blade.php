@php
    $shareUrl = $folder->share_token ? route('share.folders.show', $folder->share_token) : '';
    $shareExpiresInput = $folder->share_expires_at?->format('Y-m-d\TH:i');
    $shareViewsLabel = $folder->share_max_views
        ? $folder->share_views_count.' / '.$folder->share_max_views
        : $folder->share_views_count.' / без ліміту';
    $shareExpiresLabel = $folder->share_expires_at
        ? $folder->share_expires_at->format('d.m.Y H:i')
        : 'без дати';
@endphp

<details
    class="file-action-menu folder-action-menu"
    data-file-share
    data-share-url="{{ route('folders.share', $folder) }}"
    data-share-settings-url="{{ route('folders.share.update', $folder) }}"
    data-share-disable-url="{{ route('folders.share.destroy', $folder) }}"
>
    <summary class="button secondary action-menu-trigger folder-action-button">Дії</summary>

    <div class="file-action-panel folder-action-panel">
        <div class="action-panel-head" data-action-drag-handle>
            <strong>Папка: {{ $folder->name }}</strong>
            <button class="action-panel-close" type="button" data-action-close aria-label="Закрити меню">x</button>
        </div>

        <form class="folder-rename-form" action="{{ route('folders.update', $folder) }}" method="post" data-ajax-form>
            @csrf
            @method('patch')
            <label>
                <span>Назва папки</span>
                <input class="field" type="text" name="name" value="{{ $folder->name }}" maxlength="100" required>
            </label>

            <div class="folder-color-picker" role="radiogroup" aria-label="Колір папки">
                <label class="folder-color-swatch folder-color-swatch-none">
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

            <button class="button secondary" type="submit">Зберегти</button>
        </form>

        <div class="share-settings {{ $folder->share_token ? 'is-enabled' : '' }}">
            <div class="share-settings-head">
                <strong>Публічний лінк</strong>
                <span data-share-status>{{ $folder->share_token ? 'Активний' : 'Вимкнено' }}</span>
            </div>

            <div class="share-disabled" data-share-disabled @if ($folder->share_token) hidden @endif>
                <button class="button secondary share-inline-button" type="button" data-share-enable>Створити лінк</button>
            </div>

            <div class="share-enabled" data-share-enabled @unless ($folder->share_token) hidden @endunless>
                <label class="share-link-field">
                    <span>Лінк</span>
                    <input class="field" type="text" value="{{ $shareUrl }}" data-share-link-input readonly>
                </label>

                <div class="share-inline-actions">
                    <a class="button secondary" href="{{ $shareUrl ?: '#' }}" target="_blank" rel="noopener" data-share-open @if (! $shareUrl) aria-disabled="true" @endif>Відкрити</a>
                    <button class="button secondary" type="button" data-share-copy>Копіювати</button>
                    <button class="button danger" type="button" data-share-disable>Закрити</button>
                </div>

                <div class="share-limit-grid">
                    <label>
                        <span>Кількість переглядів</span>
                        <input class="field" type="number" min="1" max="1000000" name="share_max_views" value="{{ $folder->share_max_views }}" placeholder="Без ліміту" data-share-max-views>
                    </label>
                    <label>
                        <span>Доступний до</span>
                        <input class="field" type="datetime-local" name="share_expires_at" value="{{ $shareExpiresInput }}" data-share-expires-at>
                    </label>
                </div>

                <div class="share-usage" data-share-usage>
                    Переглядів: {{ $shareViewsLabel }} · Доступний до: {{ $shareExpiresLabel }}
                </div>

                <button class="button secondary share-save-button" type="button" data-share-save>Зберегти ліміт</button>
            </div>

            <p class="share-message" data-share-message></p>
        </div>

        <form action="{{ route('folders.destroy', $folder) }}" method="post" data-ajax-form>
            @csrf
            @method('delete')
            <button class="action-line danger" type="submit">Видалити папку і записи файлів</button>
        </form>
    </div>
</details>
