@extends('layouts.site')

@section('title', $file->original_name.' — адмінка')
@section('robots', 'noindex, nofollow')

@section('content')
    <header class="topbar">
        <a class="brand" href="{{ route('admin.users.show', $user) }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>{{ $file->original_name }}</strong>
                <p>Файл користувача {{ $user->name }}</p>
            </div>
        </a>

        <div class="nav-actions">
            <a class="button secondary" href="{{ route('admin.users.show', $user) }}">До файлів користувача</a>
            <a class="button" href="{{ route('admin.users.files.download', [$user, $file]) }}">Скачати</a>
        </div>
    </header>

    <section class="panel preview-shell">
        <div class="preview-toolbar">
            <div class="preview-title">
                <h1>{{ $file->original_name }}</h1>
                <p>{{ $file->mime_type ?? 'unknown' }} · {{ $file->human_size }} · {{ $file->folder?->name ?? 'Без папки' }} · {{ $file->storage_label }}</p>
                @if ($file->is_telegram)
                    <p>Chat ID: {{ $file->telegram_chat_id }} · Message ID: {{ $file->telegram_message_id }}</p>
                @endif
            </div>
        </div>

        <div class="preview-frame">
            @if ($file->is_image)
                <img class="preview-image" src="{{ route('admin.users.files.inline', [$user, $file]) }}" alt="{{ $file->original_name }}">
            @else
                @if ($isTruncated)
                    <p class="truncated-note">Показано перший 1 MB файла. Повну версію можна скачати.</p>
                @endif
                <pre class="text-preview">{{ $content }}</pre>
            @endif
        </div>
    </section>
@endsection
