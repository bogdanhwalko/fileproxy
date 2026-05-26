@extends('layouts.site')

@php
    $isEdit = isset($file) && $file !== null;
    $formAction = $isEdit ? route('files.update-doc', $file) : route('files.store-doc');
    $pageTitle = $isEdit ? 'Редагувати документ: '.$file->original_name : 'Новий документ';
    $oldTitle = old('title', $isEdit ? $documentTitle : '');
    $oldContent = old('content', $isEdit ? $documentHtml : '');
@endphp

@section('title', $pageTitle.' — FileProxy')
@section('robots', 'noindex, nofollow')

@push('head')
    {{-- Quill 2 — modern, MIT, small, with rich toolbar --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
@endpush

@section('content')
    <x-app-topbar title="FileProxy" subtitle="WYSIWYG-редактор документа" />

    @if ($errors->any())
        <div class="errors">
            <strong>Перевірте дані форми.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel doc-editor-shell" data-doc-editor>
        <header class="doc-editor-head">
            <a class="button secondary doc-editor-back" href="{{ route('files.index', $activeFolder ? ['folder' => $activeFolder->id] : []) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                <span>Назад</span>
            </a>
            <div class="doc-editor-head-title">
                <h1>
                    @if ($isEdit)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Редагування документа
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="9" y1="13" x2="15" y2="13"/>
                            <line x1="9" y1="17" x2="13" y2="17"/>
                        </svg>
                        Новий документ
                    @endif
                </h1>
                <span>Word-like редактор · збереже як .html · до 5 MB</span>
            </div>
        </header>

        <form action="{{ $formAction }}" method="post" class="doc-editor-form" data-doc-editor-form>
            @csrf
            @if ($isEdit)
                @method('patch')
            @endif

            <div class="doc-editor-controls">
                <div class="doc-editor-control doc-editor-control-title">
                    <label for="doc-editor-title">Назва документа</label>
                    <input
                        id="doc-editor-title"
                        class="field"
                        type="text"
                        name="title"
                        value="{{ $oldTitle }}"
                        maxlength="200"
                        placeholder="Мій документ"
                        required
                        autocomplete="off"
                        data-doc-editor-title>
                </div>

                @unless ($isEdit)
                    <div class="doc-editor-control">
                        <label for="doc-editor-folder">Папка</label>
                        <select id="doc-editor-folder" class="field" name="folder_id">
                            <option value="">Без папки</option>
                            @foreach ($folders as $folder)
                                <option value="{{ $folder->id }}" @selected(old('folder_id', $activeFolder?->id) == $folder->id)>{{ $folder->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($telegramStorageGroups->isNotEmpty() || ! $canUseLocalStorage)
                        <div class="doc-editor-control">
                            <label for="doc-editor-storage">Сховище</label>
                            <select id="doc-editor-storage" class="field" name="telegram_storage_group_id">
                                @if ($canUseLocalStorage)
                                    <option value="">Локальне</option>
                                @endif
                                @foreach ($telegramStorageGroups as $group)
                                    <option value="{{ $group->id }}" @selected(old('telegram_storage_group_id') == $group->id || (! $canUseLocalStorage && $group->is_default))>
                                        {{ $group->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @endunless
            </div>

            {{-- Quill mounts here. Hidden textarea carries serialized HTML for submit. --}}
            <div class="doc-editor-body">
                <div class="doc-editor-quill" data-doc-editor-quill></div>
            </div>
            <textarea name="content" hidden data-doc-editor-content>{{ $oldContent }}</textarea>

            <footer class="doc-editor-footer">
                <div class="doc-editor-stats">
                    <span><strong data-doc-editor-words>0</strong> слів</span>
                    <span><strong data-doc-editor-chars>0</strong> символів</span>
                </div>
                <div class="doc-editor-footer-hint">
                    <kbd>Ctrl</kbd>+<kbd>S</kbd> — зберегти, <kbd>Ctrl</kbd>+<kbd>B</kbd>/<kbd>I</kbd>/<kbd>U</kbd> — форматування
                </div>
                <div class="doc-editor-actions">
                    <a class="button secondary" href="{{ route('files.index', $activeFolder ? ['folder' => $activeFolder->id] : []) }}">Скасувати</a>
                    <button type="submit" class="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Зберегти
                    </button>
                </div>
            </footer>
        </form>
    </section>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js" defer></script>
        <script src="@vasset('js/doc-editor.js')" defer></script>
    @endpush
@endsection
