@extends('layouts.site')

@section('title', $folder->name.' — FileProxy')
@section('robots', 'noindex, nofollow')

@section('content')
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Публічний доступ до папки</p>
            </div>
        </a>

        <div class="nav-actions">
            @auth
                <a class="button secondary" href="{{ route('files.index') }}">Мої файли</a>
            @else
                <a class="button secondary" href="{{ route('login') }}">Увійти</a>
            @endauth
            <a class="button" href="{{ route('share.folders.download', $folder->share_token) }}">Скачати ZIP</a>
        </div>
    </header>

    <section class="panel public-folder-shell">
        <div class="panel-header public-folder-header">
            <div>
                <span class="share-badge">Публічна папка</span>
                <h2>{{ $folder->name }}</h2>
                <p>Доступні файли: {{ $files->total() }}. Папку можна скачати одним ZIP-архівом або відкривати файли окремо.</p>
            </div>
            <a class="button" href="{{ route('share.folders.download', $folder->share_token) }}">Скачати все</a>
        </div>

        <div class="table-wrap">
            <table class="compact-file-table public-share-table">
                <thead>
                    <tr>
                        <th>Файл</th>
                        <th>Тип</th>
                        <th>Розмір</th>
                        <th>Дата</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($files as $file)
                        <tr>
                            <td>
                                <div class="file-table-name">
                                    <span class="file-icon file-icon-cat-{{ $file->type_category }}">{{ $file->type_label }}</span>
                                    <div class="file-table-title">
                                        <strong title="{{ $file->original_name }}">{{ $file->original_name }}</strong>
                                        <span>{{ $file->mime_type ?? 'unknown' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="muted">{{ $file->extension ? strtoupper($file->extension) : 'FILE' }}</td>
                            <td>{{ $file->human_size }}</td>
                            <td class="muted">@reltime($file->created_at)</td>
                            <td>
                                <div class="file-row-actions">
                                    @if ($file->is_previewable)
                                        <a class="button accent" href="{{ route('share.folders.files.show', [$folder->share_token, $file]) }}">Переглянути</a>
                                    @endif
                                    <a class="button secondary" href="{{ route('share.folders.files.download', [$folder->share_token, $file]) }}">Скачати</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty">У цій папці поки немає файлів.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($files->hasPages())
            <nav class="pagination" aria-label="Сторінки файлів">
                @if ($files->onFirstPage())
                    <span>Назад</span>
                @else
                    <a class="button secondary" href="{{ $files->previousPageUrl() }}">Назад</a>
                @endif
                <span>Сторінка {{ $files->currentPage() }} з {{ $files->lastPage() }}</span>
                @if ($files->hasMorePages())
                    <a class="button secondary" href="{{ $files->nextPageUrl() }}">Далі</a>
                @else
                    <span>Далі</span>
                @endif
            </nav>
        @endif
    </section>
@endsection
