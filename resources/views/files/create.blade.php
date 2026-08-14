@extends('layouts.site')

@section('title', 'Створити файл — FileProxy')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-app-topbar title="FileProxy" subtitle="Створення нового файлу" />

    <section class="panel file-create-shell">
        <header class="file-create-head">
            <a class="button secondary file-create-back" href="{{ route('files.index', $activeFolder ? ['folder' => $activeFolder->id] : []) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                <span>Назад</span>
            </a>
            <div class="file-create-head-title">
                <h1>Створити файл</h1>
                <p>Оберіть тип файлу, який хочете створити просто в браузері@if ($activeFolder) — потрапить у папку «{{ $activeFolder->name }}»@endif.</p>
            </div>
        </header>

        <div class="file-create-grid">
            <a class="upload-text-create" href="{{ route('files.create-text', $activeFolder ? ['folder' => $activeFolder->id] : []) }}" title="Створити новий текстовий файл / код у браузері">
                <span class="upload-text-create-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 18 22 12 16 6"/>
                        <polyline points="8 6 2 12 8 18"/>
                    </svg>
                </span>
                <span class="upload-text-create-text">
                    <strong>Код / текст</strong>
                    <span>Із підсвіткою · php, js, md, json, …</span>
                </span>
            </a>

            <a class="upload-doc-create" href="{{ route('files.create-doc', $activeFolder ? ['folder' => $activeFolder->id] : []) }}" title="Створити форматований документ як у Word">
                <span class="upload-doc-create-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="9" y1="13" x2="15" y2="13"/>
                        <line x1="9" y1="17" x2="13" y2="17"/>
                    </svg>
                </span>
                <span class="upload-doc-create-text">
                    <strong>Документ</strong>
                    <span>Word-like · заголовки, списки, форматування</span>
                </span>
            </a>
        </div>
    </section>
@endsection
