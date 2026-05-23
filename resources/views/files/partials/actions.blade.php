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
@endphp

<details
    class="file-action-menu"
    data-file-share
    data-share-url="{{ route('files.share', $file) }}"
    data-share-settings-url="{{ route('files.share.update', $file) }}"
    data-share-disable-url="{{ route('files.share.destroy', $file) }}"
>
    <summary class="button secondary action-menu-trigger">Дії</summary>

    <div class="file-action-panel">
        <div class="action-panel-head" data-action-drag-handle>
            <strong>Дії з файлом</strong>
            <button class="action-panel-close" type="button" data-action-close aria-label="Закрити меню">x</button>
        </div>

        <div class="action-menu-links">
            @if ($file->is_uploaded)
                @if ($file->is_previewable)
                    <a class="action-line accent" href="{{ route('files.preview', $file) }}">Переглянути</a>
                @endif
                <a class="action-line" href="{{ route('files.download', $file) }}">Скачати</a>
            @endif
            <form action="{{ route('files.destroy', $file) }}" method="post" data-ajax-form>
                @csrf
                @method('delete')
                <button class="action-line danger" type="submit">{{ $file->is_uploaded ? 'Видалити' : 'Скасувати' }}</button>
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

        @if ($file->is_uploaded)
        <div class="share-settings {{ $file->share_token ? 'is-enabled' : '' }}">
            <div class="share-settings-head">
                <strong>Публічний лінк</strong>
                <span data-share-status>{{ $file->share_token ? 'Активний' : 'Вимкнено' }}</span>
            </div>

            <div class="share-disabled" data-share-disabled @if ($file->share_token) hidden @endif>
                <button class="button secondary share-inline-button" type="button" data-share-enable>Створити лінк</button>
            </div>

            <div class="share-enabled" data-share-enabled @unless ($file->share_token) hidden @endunless>
                <label class="share-link-field">
                    <span>Сторінка перегляду</span>
                    <input class="field" type="text" value="{{ $shareUrl }}" data-share-link-input readonly>
                </label>

                <div class="share-inline-actions">
                    <a class="button secondary" href="{{ $shareUrl ?: '#' }}" target="_blank" rel="noopener" data-share-open @if (! $shareUrl) aria-disabled="true" @endif>Відкрити</a>
                    <button class="button secondary" type="button" data-share-copy>Копіювати</button>
                    <button class="button danger" type="button" data-share-disable>Закрити</button>
                </div>

                <label class="share-link-field share-link-field-raw">
                    <span>Прямий лінк на файл</span>
                    <input class="field" type="text" value="{{ $shareRawUrl }}" data-share-raw-link-input readonly>
                </label>
                <div class="share-inline-actions share-inline-actions-raw">
                    <a class="button secondary" href="{{ $shareRawUrl ?: '#' }}" target="_blank" rel="noopener" data-share-raw-open @if (! $shareRawUrl) aria-disabled="true" @endif>Відкрити</a>
                    <button class="button secondary" type="button" data-share-raw-copy>Копіювати</button>
                </div>
                <p class="share-raw-hint">Без сторінки сайту — лінк віддає сам файл (зручно для месенджерів, embed'ів).</p>

                <div class="share-limit-grid">
                    <label>
                        <span>Кількість переглядів</span>
                        <input class="field" type="number" min="1" max="1000000" name="share_max_views" value="{{ $file->share_max_views }}" placeholder="Без ліміту" data-share-max-views>
                    </label>
                    <label>
                        <span>Доступний до</span>
                        <input class="field" type="datetime-local" name="share_expires_at" value="{{ $shareExpiresInput }}" data-share-expires-at>
                    </label>
                </div>

                <div class="share-usage" data-share-usage>
                    Переглядів: {{ $shareViewsLabel }} · Доступний до: {{ $shareExpiresLabel }}
                </div>

                <div class="share-paid-block" data-share-paid-block>
                    <label class="share-paid-toggle">
                        <input type="checkbox" name="is_paid" value="1" {{ $file->is_paid ? 'checked' : '' }} data-share-paid-toggle>
                        <span>Зробити файл платним</span>
                    </label>

                    <div class="share-paid-fields" data-share-paid-fields @unless ($file->is_paid) hidden @endunless>
                        <div class="share-paid-grid">
                            <label>
                                <span>Ціна</span>
                                <input class="field" type="number" step="0.01" min="0.5" max="1000" name="price" value="{{ $file->price_cents ? number_format($file->price_cents / 100, 2, '.', '') : '' }}" placeholder="2.99" data-share-price>
                            </label>
                            <label>
                                <span>Валюта</span>
                                <select class="field" name="currency" data-share-currency>
                                    <option value="USD" {{ ($file->currency ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                    <option value="EUR" {{ ($file->currency ?? '') === 'EUR' ? 'selected' : '' }}>EUR</option>
                                </select>
                            </label>
                            <label>
                                <span>Скачувань на покупку</span>
                                <input class="field" type="number" min="1" max="1000" name="purchase_max_downloads" value="{{ $file->purchase_max_downloads }}" placeholder="Без ліміту" data-share-purchase-max-downloads>
                            </label>
                            <label>
                                <span>Доступ (годин)</span>
                                <input class="field" type="number" min="1" max="8760" name="purchase_access_hours" value="{{ $file->purchase_access_hours }}" placeholder="Без ліміту" data-share-purchase-access-hours>
                            </label>
                        </div>
                        <p class="share-paid-hint">При увімкненому платному файлі публічний лінк показує сторінку оплати замість прямого доступу.</p>
                    </div>
                </div>

                <button class="button secondary share-save-button" type="button" data-share-save>Зберегти налаштування</button>
            </div>

            <p class="share-message" data-share-message></p>
        </div>
        @endif
    </div>
</details>
