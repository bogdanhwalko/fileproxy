@extends('layouts.site')

@section('title', $file->original_name.' — адмінка')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-app-topbar
        :title="$file->original_name"
        :subtitle="'Файл користувача '.$user->name"
        :brandHref="route('admin.users.show', $user)"
    >
        <a class="button secondary nav-button" href="{{ route('admin.users.show', $user) }}" aria-label="До файлів користувача">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5"/>
                <polyline points="12 19 5 12 12 5"/>
            </svg>
            <span class="nav-button-label">До файлів користувача</span>
        </a>
        <a class="button nav-button" href="{{ route('admin.users.files.download', [$user, $file]) }}" aria-label="Скачати">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            <span class="nav-button-label">Скачати</span>
        </a>
    </x-app-topbar>

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
