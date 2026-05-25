@extends('layouts.site')

@section('title', $folder->name.' — пароль необхідний')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-app-topbar title="FileProxy" subtitle="Папка захищена паролем" />

    <section class="folder-unlock">
        <div class="folder-unlock-card">
            <div class="folder-unlock-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h1>Папка захищена</h1>
            <p class="folder-unlock-name">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
                {{ $folder->name }}
            </p>
            <p class="folder-unlock-hint">Введіть пароль, щоб переглянути файли. Сесія розблокування — 30 хвилин.</p>

            @if ($errors->any())
                <div class="errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('folders.unlock', $folder) }}" class="folder-unlock-form">
                @csrf
                <input type="password"
                    name="password"
                    class="field folder-unlock-input"
                    placeholder="Пароль папки"
                    autocomplete="current-password"
                    autofocus
                    required>
                <button type="submit" class="button">
                    Розблокувати
                </button>
            </form>

            <a class="folder-unlock-back" href="{{ route('files.index') }}">← До всіх файлів</a>
        </div>
    </section>
@endsection
