@extends('layouts.site')

@section('title', $file->original_name.' — FileProxy')
@section('robots', 'noindex, nofollow')

@section('content')
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Публічний доступ до файла</p>
            </div>
        </a>

        <div class="nav-actions">
            <a class="button secondary" href="{{ $backUrl }}">{{ $backLabel }}</a>
            @auth
                <a class="button secondary" href="{{ route('files.index') }}">Мої файли</a>
            @else
                <a class="button secondary" href="{{ route('login') }}">Увійти</a>
            @endauth
            <a class="button" href="{{ $downloadUrl }}">Скачати</a>
        </div>
    </header>

    <section class="panel preview-shell public-share-shell">
        <div class="preview-toolbar">
            <div class="preview-title">
                <span class="share-badge">{{ $contextLabel }}</span>
                <h1>{{ $file->original_name }}</h1>
                <p>{{ $file->mime_type ?? 'unknown' }} · {{ $file->human_size }}</p>
            </div>
            <div class="share-toolbar-actions">
                <a class="button" href="{{ $downloadUrl }}">Скачати файл</a>
            </div>
        </div>

        <div class="preview-frame">
            @if ($file->is_image && $inlineUrl)
                <img class="preview-image" src="{{ $inlineUrl }}" alt="{{ $file->original_name }}">
            @elseif ($file->is_text)
                @if ($isTruncated)
                    <p class="truncated-note">Показано перший 1 MB файла. Повну версію можна скачати.</p>
                @endif
                <pre class="text-preview">{{ $content }}</pre>
            @else
                <div class="empty share-empty">
                    Попередній перегляд для цього типу файла недоступний. Файл можна скачати напряму.
                </div>
            @endif
        </div>
    </section>
@endsection
