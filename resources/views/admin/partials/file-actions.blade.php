<details class="file-action-menu">
    <summary class="button secondary action-menu-trigger">Дії</summary>

    <div class="file-action-panel">
        <div class="action-panel-head" data-action-drag-handle>
            <strong>Дії з файлом</strong>
            <button class="action-panel-close" type="button" data-action-close aria-label="Закрити меню">x</button>
        </div>

        <div class="action-menu-links">
            @if ($file->is_uploaded)
                @if ($file->is_previewable)
                    <a class="action-line accent" href="{{ route('admin.users.files.preview', [$user, $file]) }}">Переглянути</a>
                @endif
                <a class="action-line" href="{{ route('admin.users.files.download', [$user, $file]) }}">Скачати</a>
            @endif
            <form action="{{ route('admin.users.files.destroy', [$user, $file]) }}" method="post" data-ajax-form>
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
                    <p>Файл ще завантажується в Telegram.</p>
                @endif
            </div>
        @endif

        @if ($file->is_telegram && ($file->telegram_chat_id || $file->telegram_message_id))
            <div class="file-status-note">
                <strong>Telegram метадані</strong>
                <p>chat: {{ $file->telegram_chat_id ?? '—' }} · msg: {{ $file->telegram_message_id ?? '—' }}</p>
            </div>
        @endif
    </div>
</details>
