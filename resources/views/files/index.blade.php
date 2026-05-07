@extends('layouts.site')

@section('title', 'Файли — FileProxy')
@section('robots', 'noindex, nofollow')

@section('content')
    <header class="topbar topbar-v2">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Завантаження, папки і швидке керування файлами</p>
            </div>
        </a>

        <div class="nav-actions">
            <span class="user-chip user-chip-v2">
                <span class="user-chip-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) ?: 'U' }}</span>
                <span class="user-chip-name">{{ auth()->user()->name }}</span>
            </span>
            @if (auth()->user()->is_admin)
                <a class="button secondary nav-button" href="{{ route('admin.users.index') }}" aria-label="Адмінка">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5z"/>
                    </svg>
                    <span class="nav-button-label">Адмінка</span>
                </a>
            @endif
            <a class="button secondary nav-button" href="{{ route('stats.index') }}" aria-label="Статистика">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span class="nav-button-label">Статистика</span>
            </a>
            <a class="button secondary nav-button" href="{{ route('telegram-settings.index') }}" aria-label="Telegram-сховище">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 2 11 13"/>
                    <path d="M22 2 15 22l-4-9-9-4z"/>
                </svg>
                <span class="nav-button-label">Telegram-сховище</span>
            </a>
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button class="button secondary nav-button nav-button-logout" type="submit" aria-label="Вийти">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span class="nav-button-label">Вийти</span>
                </button>
            </form>
        </div>
    </header>

    <div data-flash-area>
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

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
    </div>

    <section class="panel upload-panel upload-panel-v2">
        <header class="upload-hero upload-hero-compact">
            <div class="upload-hero-eyebrow">
                <span class="upload-hero-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </span>
                <span class="upload-hero-title">Нове завантаження</span>
            </div>

            <div class="upload-hero-meta">
                <span class="upload-hero-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    до <strong>{{ $telegramUploadMaxMb }} MB</strong> на файл
                </span>

                @if (! $canUseLocalStorage && $telegramStorageGroups->isEmpty() && $systemTelegramStorageAvailable)
                    <span class="upload-hero-meta-item upload-hero-meta-info">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 2 11 13"/>
                            <path d="M22 2 15 22l-4-9-9-4z"/>
                        </svg>
                        Системне: <strong>{{ $systemTelegramRemainingUploads }} / {{ $systemTelegramUploadLimit }}</strong>
                    </span>
                @endif
            </div>
        </header>

        <form class="upload-form-v2" action="{{ route('files.store') }}" method="post" enctype="multipart/form-data" data-upload-form>
            @csrf

            <label class="dropzone-v2" data-dropzone>
                <input type="file" name="files[]" multiple required data-upload-input>

                <span class="dropzone-v2-pulse" aria-hidden="true"></span>

                <div class="dropzone-v2-graphic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </div>

                <div class="dropzone-v2-body">
                    <strong>Перетягніть файли сюди</strong>
                    <span>підтримується кілька файлів за раз, до {{ $telegramUploadMaxMb }} MB кожен</span>
                </div>

                <span class="dropzone-v2-cta">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                    </svg>
                    Обрати файли з пристрою
                </span>

                <small class="dropzone-v2-hint">
                    @if ($canUseLocalStorage)
                        Локально або Telegram — залежно від обраного сховища нижче.
                    @else
                        Файли потраплять у Telegram-сховище.
                    @endif
                </small>
            </label>

            <div class="upload-selected" data-upload-selected hidden>
                <div class="upload-selected-head">
                    <div class="upload-selected-summary">
                        <strong>Обрано: <span data-upload-count>0</span></strong>
                        <span data-upload-total-size>0 KB</span>
                    </div>
                    <button type="button" class="button link upload-selected-clear" data-upload-clear>Очистити список</button>
                </div>
                <ul class="upload-selected-list" data-upload-list></ul>
            </div>

            <div class="upload-controls">
                @php
                    $folderOptions = collect([
                        ['value' => '', 'label' => 'Без папки', 'sublabel' => 'У корені сховища', 'is_selected' => $activeFolder === null],
                    ]);

                    foreach ($folders as $folder) {
                        $folderOptions->push([
                            'value' => (string) $folder->id,
                            'label' => $folder->name,
                            'sublabel' => ($folder->files_count ?? 0).' '.(($folder->files_count ?? 0) === 1 ? 'файл' : 'файлів'),
                            'is_selected' => $activeFolder?->id === $folder->id,
                        ]);
                    }

                    $folderSelected = $folderOptions->firstWhere('is_selected', true) ?: $folderOptions->first();

                    $storageOptions = collect();

                    if ($canUseLocalStorage) {
                        $storageOptions->push([
                            'value' => '',
                            'label' => 'Локальне сховище',
                            'sublabel' => 'Файли на сервері',
                            'icon' => 'server',
                            'is_selected' => true,
                        ]);
                    } elseif ($telegramStorageGroups->isEmpty() && $systemTelegramStorageAvailable) {
                        $storageOptions->push([
                            'value' => '',
                            'label' => 'Системне Telegram-сховище',
                            'sublabel' => "Залишилось {$systemTelegramRemainingUploads} з {$systemTelegramUploadLimit}",
                            'icon' => 'tg',
                            'is_selected' => true,
                        ]);
                    } elseif ($telegramStorageGroups->isEmpty()) {
                        $storageOptions->push([
                            'value' => '',
                            'label' => 'Telegram-сховище не налаштоване',
                            'sublabel' => 'Підключіть бота і групу в налаштуваннях',
                            'icon' => 'warn',
                            'is_selected' => true,
                        ]);
                    } else {
                        $oldStorageId = (string) old('telegram_storage_group_id', '');
                        $hasSelected = false;
                        foreach ($telegramStorageGroups as $storageGroup) {
                            $isThisSelected = $oldStorageId !== ''
                                ? $oldStorageId === (string) $storageGroup->id
                                : (bool) $storageGroup->is_default;

                            if ($isThisSelected) $hasSelected = true;

                            $storageOptions->push([
                                'value' => (string) $storageGroup->id,
                                'label' => $storageGroup->title,
                                'sublabel' => '@'.($storageGroup->botToken?->username ?: $storageGroup->botToken?->name ?? 'бот'),
                                'icon' => 'tg',
                                'is_selected' => $isThisSelected,
                            ]);
                        }
                        if (! $hasSelected && $storageOptions->isNotEmpty()) {
                            $storageOptions[0]['is_selected'] = true;
                        }
                    }

                    $storageSelected = $storageOptions->firstWhere('is_selected', true) ?: $storageOptions->first();
                @endphp

                {{-- Folder dropdown --}}
                <div class="upload-control upload-control-folder" data-upload-dropdown>
                    <button type="button" class="upload-control-trigger" data-upload-dropdown-trigger aria-haspopup="listbox" aria-expanded="false">
                        <span class="upload-control-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            </svg>
                        </span>
                        <span class="upload-control-body">
                            <span class="upload-control-label">Папка</span>
                            <span class="upload-control-value" data-upload-dropdown-value>{{ $folderSelected['label'] ?? 'Без папки' }}</span>
                        </span>
                        <span class="upload-control-chevron" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </span>
                    </button>

                    <div class="upload-dropdown-menu" data-upload-dropdown-menu role="listbox" hidden>
                        @foreach ($folderOptions as $opt)
                            <button
                                type="button"
                                class="upload-dropdown-option {{ $opt['is_selected'] ? 'is-selected' : '' }}"
                                data-upload-dropdown-option
                                data-value="{{ $opt['value'] }}"
                                role="option"
                                aria-selected="{{ $opt['is_selected'] ? 'true' : 'false' }}"
                            >
                                <span class="upload-dropdown-option-icon" aria-hidden="true">
                                    @if ($opt['value'] === '')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="9"/>
                                            <line x1="6" y1="6" x2="18" y2="18"/>
                                        </svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                        </svg>
                                    @endif
                                </span>
                                <span class="upload-dropdown-option-body">
                                    <strong>{{ $opt['label'] }}</strong>
                                    <span>{{ $opt['sublabel'] }}</span>
                                </span>
                                <span class="upload-dropdown-option-check" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <input type="hidden" name="folder_id" id="folder_id" data-upload-dropdown-input value="{{ $folderSelected['value'] ?? '' }}">
                </div>

                {{-- Storage dropdown --}}
                <div class="upload-control upload-control-storage" data-upload-dropdown>
                    <button type="button" class="upload-control-trigger" data-upload-dropdown-trigger aria-haspopup="listbox" aria-expanded="false">
                        <span class="upload-control-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.5 19a4.5 4.5 0 0 0 .5-8.97 7 7 0 0 0-13.74 2.05A4 4 0 0 0 5 19z"/>
                            </svg>
                        </span>
                        <span class="upload-control-body">
                            <span class="upload-control-label">Сховище</span>
                            <span class="upload-control-value" data-upload-dropdown-value>{{ $storageSelected['label'] ?? 'Не обрано' }}</span>
                        </span>
                        <span class="upload-control-chevron" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </span>
                    </button>

                    <div class="upload-dropdown-menu" data-upload-dropdown-menu role="listbox" hidden>
                        @forelse ($storageOptions as $opt)
                            <button
                                type="button"
                                class="upload-dropdown-option {{ $opt['is_selected'] ? 'is-selected' : '' }}"
                                data-upload-dropdown-option
                                data-value="{{ $opt['value'] }}"
                                role="option"
                                aria-selected="{{ $opt['is_selected'] ? 'true' : 'false' }}"
                            >
                                <span class="upload-dropdown-option-icon upload-dropdown-option-icon-{{ $opt['icon'] ?? 'tg' }}" aria-hidden="true">
                                    @if (($opt['icon'] ?? '') === 'server')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="2" y="2" width="20" height="8" rx="2"/>
                                            <rect x="2" y="14" width="20" height="8" rx="2"/>
                                            <line x1="6" y1="6" x2="6.01" y2="6"/>
                                            <line x1="6" y1="18" x2="6.01" y2="18"/>
                                        </svg>
                                    @elseif (($opt['icon'] ?? '') === 'warn')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                            <line x1="12" y1="9" x2="12" y2="13"/>
                                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                                        </svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 2 11 13"/>
                                            <path d="M22 2 15 22l-4-9-9-4z"/>
                                        </svg>
                                    @endif
                                </span>
                                <span class="upload-dropdown-option-body">
                                    <strong>{{ $opt['label'] }}</strong>
                                    <span>{{ $opt['sublabel'] }}</span>
                                </span>
                                <span class="upload-dropdown-option-check" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </span>
                            </button>
                        @empty
                        @endforelse
                    </div>

                    <input type="hidden" name="telegram_storage_group_id" id="telegram_storage_group_id" data-upload-dropdown-input value="{{ $storageSelected['value'] ?? '' }}">
                </div>
            </div>

            @if (! $canUseLocalStorage && $telegramStorageGroups->isEmpty() && ! $systemTelegramStorageAvailable)
                <div class="upload-warning">
                    Власну Telegram-групу ще не підключено. Перейдіть у <a href="{{ route('telegram-settings.index') }}">налаштування Telegram</a>, додайте бота і додайте його у вашу групу — група зʼявиться автоматично.
                </div>
            @elseif (! $canUseLocalStorage && $telegramStorageGroups->isEmpty() && $systemTelegramStorageAvailable)
                <div class="upload-warning">
                    Поки використовується системне Telegram-сховище (ліміт {{ $systemTelegramRemainingUploads }} файлів). Підключіть власного бота в <a href="{{ route('telegram-settings.index') }}">налаштуваннях</a>, щоб зняти ліміт.
                </div>
            @endif

            <div class="upload-progress" data-upload-progress hidden>
                <div class="upload-progress-head">
                    <strong data-upload-progress-label>Підготовка до завантаження...</strong>
                    <span data-upload-progress-percent>0%</span>
                </div>
                <div class="upload-progress-track" aria-hidden="true">
                    <span data-upload-progress-bar style="width: 0%"></span>
                </div>
                <p data-upload-progress-note>Прогрес приблизний: після передачі файлів сервер ще може обробляти Telegram-сховище.</p>
            </div>

            <div class="upload-footer upload-footer-v2">
                <div class="upload-actions upload-actions-v2">
                    @if (! $telegramStorageGroups->count() && ! $systemTelegramStorageAvailable)
                        <a class="button secondary" href="{{ route('telegram-settings.index') }}">Як прив’язати Telegram</a>
                    @elseif (! $telegramStorageGroups->count())
                        <a class="button secondary" href="{{ route('telegram-settings.index') }}">Власне Telegram-сховище</a>
                    @endif
                    <button class="button upload-submit-btn" type="submit" data-upload-submit>
                        <svg class="upload-submit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <span data-upload-submit-label>Завантажити</span>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="workspace">
        <aside class="sidebar-stack">
            <section class="panel folders-panel-v2">
                <header class="folders-header-v2">
                    <div class="folders-header-title">
                        <span class="folders-header-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            </svg>
                        </span>
                        <h2>Папки</h2>
                        <span class="folders-header-count">{{ $folders->count() }}</span>
                    </div>
                    <button type="button" class="folders-add-toggle" data-folders-add-toggle aria-label="Створити нову папку" title="Створити нову папку">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </button>
                </header>

                <form class="folders-form-v2" action="{{ route('folders.store') }}" method="post" data-ajax-form data-folders-form hidden>
                    @csrf
                    <input class="field" type="text" name="name" value="{{ old('name') }}" placeholder="Назва нової папки" maxlength="100" data-folders-form-input required>
                    <div class="folders-form-actions">
                        <button class="button secondary" type="button" data-folders-form-cancel>Скасувати</button>
                        <button class="button" type="submit">Створити</button>
                    </div>
                </form>

                <nav class="folders-list-v2" aria-label="Список папок">
                    <a class="folder-item folder-item-pinned {{ $folderFilter === 'all' ? 'is-active' : '' }}" href="{{ route('files.index') }}">
                        <span class="folder-item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                                <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                            </svg>
                        </span>
                        <span class="folder-item-name">Усі файли</span>
                        <span class="folder-item-count">{{ $stats['total'] }}</span>
                    </a>

                    <a class="folder-item folder-item-pinned {{ $folderFilter === 'root' ? 'is-active' : '' }}" href="{{ route('files.index', ['folder' => 'root']) }}">
                        <span class="folder-item-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </span>
                        <span class="folder-item-name">Без папки</span>
                        <span class="folder-item-count">{{ $stats['root'] }}</span>
                    </a>

                    @if ($folders->isNotEmpty())
                        <div class="folders-divider" aria-hidden="true"></div>
                    @endif

                    @foreach ($folders as $folder)
                        <div class="folder-row-v2">
                            <a class="folder-item {{ $activeFolder?->id === $folder->id ? 'is-active' : '' }}" href="{{ route('files.index', ['folder' => $folder->id]) }}">
                                <span class="folder-item-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    </svg>
                                </span>
                                <span class="folder-item-name">{{ $folder->name }}</span>
                                <span class="folder-item-count">{{ $folder->files_count }}</span>
                            </a>
                            <div class="folder-actions">
                                @include('files.partials.folder-actions', ['folder' => $folder])
                            </div>
                        </div>
                    @endforeach
                </nav>
            </section>
        </aside>

        <section class="panel" aria-label="Список файлів" data-files-region>
            <form class="filters filters-v2" action="{{ route('files.index') }}" method="get" data-ajax-filter>
                @if ($folderFilter !== 'all')
                    <input type="hidden" name="folder" value="{{ $folderFilter }}">
                @endif
                <input type="hidden" name="view" value="{{ $display }}">

                <div class="filter-field filter-field-search">
                    <label for="filter-search">Пошук</label>
                    <input id="filter-search" class="field" type="search" name="search" value="{{ $search }}" placeholder="Назва, MIME або розширення">
                </div>

                <div class="filter-field filter-field-type">
                    <label for="filter-type">Тип</label>
                    <select id="filter-type" class="field" name="type">
                        <option value="all" @selected($type === 'all')>Усі типи</option>
                        <option value="images" @selected($type === 'images')>Зображення</option>
                        <option value="videos" @selected($type === 'videos')>Відео</option>
                        <option value="audio" @selected($type === 'audio')>Аудіо</option>
                        <option value="documents" @selected($type === 'documents')>Документи</option>
                        <option value="spreadsheets" @selected($type === 'spreadsheets')>Таблиці</option>
                        <option value="presentations" @selected($type === 'presentations')>Презентації</option>
                        <option value="archives" @selected($type === 'archives')>Архіви</option>
                        <option value="code" @selected($type === 'code')>Код</option>
                        <option value="design" @selected($type === 'design')>Дизайн</option>
                        <option value="ebooks" @selected($type === 'ebooks')>Книги</option>
                        <option value="fonts" @selected($type === 'fonts')>Шрифти</option>
                    </select>
                </div>

                <div class="filter-field filter-field-daterange filter-daterange">
                    <label for="filter-daterange">Період</label>
                    <input
                        id="filter-daterange"
                        type="text"
                        class="field"
                        data-flatpickr-range
                        data-initial-from="{{ $dateFrom }}"
                        data-initial-to="{{ $dateTo }}"
                        placeholder="Оберіть діапазон"
                        readonly
                    >
                    <input type="hidden" name="date_from" value="{{ $dateFrom }}" data-flatpickr-from>
                    <input type="hidden" name="date_to" value="{{ $dateTo }}" data-flatpickr-to>
                </div>

                <div class="filter-actions">
                    <button class="button filter-submit" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        Фільтрувати
                    </button>
                    @php
                        $hasActiveFilter = $search !== '' || $type !== 'all' || $dateFrom !== '' || $dateTo !== '';
                        $resetUrl = route('files.index', $folderFilter !== 'all' ? ['folder' => $folderFilter, 'view' => $display] : ['view' => $display]);
                    @endphp
                    <a
                        class="button secondary filter-reset {{ $hasActiveFilter ? '' : 'is-disabled' }}"
                        href="{{ $hasActiveFilter ? $resetUrl : '#' }}"
                        @if (! $hasActiveFilter) aria-disabled="true" tabindex="-1" @endif
                        title="{{ $hasActiveFilter ? 'Скинути всі фільтри' : 'Зараз немає активних фільтрів' }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 12a9 9 0 1 0 3-6.7"/>
                            <polyline points="3 4 3 10 9 10"/>
                        </svg>
                        Скинути
                    </a>
                </div>
            </form>

            <div class="file-view-bar">
                <span data-file-summary data-total="{{ $files->total() }}">Показано {{ $files->count() }} з {{ $files->total() }}</span>
                <div class="file-view-bar-actions">
                    @if ($filteredCount > 0)
                        <a
                            class="button accent file-archive-btn"
                            href="{{ route('files.archive', request()->except(['page', 'view', 'image_previews'])) }}"
                            title="Завантажити ZIP-архів усіх файлів за поточними фільтрами ({{ $filteredCount }} {{ $filteredCount === 1 ? 'файл' : ($filteredCount < 5 ? 'файли' : 'файлів') }})."
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            <span>Архів ({{ $filteredCount }})</span>
                        </a>
                    @endif

                    <div class="view-toggle" aria-label="Вигляд списку файлів">
                        <a class="button secondary {{ $display === 'table' ? 'active' : '' }}" href="{{ route('files.index', array_merge(request()->except(['page', 'view', 'image_previews']), ['view' => 'table'])) }}">Таблиця</a>
                        <a class="button secondary {{ $display === 'grid' ? 'active' : '' }}" href="{{ route('files.index', array_merge(request()->except(['page', 'view']), ['view' => 'grid'])) }}">Плитки</a>
                        @if ($display === 'grid')
                            <a
                                class="button secondary {{ $imagePreviews ? 'active' : '' }}"
                                href="{{ route('files.index', $imagePreviews
                                    ? array_merge(request()->except(['page', 'image_previews']), ['view' => 'grid'])
                                    : array_merge(request()->except(['page']), ['view' => 'grid', 'image_previews' => 1])) }}"
                            >
                                {{ $imagePreviews ? 'Фото увімкнено' : 'Передзавантажити фото' }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @if ($display === 'grid')
                <div class="file-grid {{ $imagePreviews ? 'with-previews' : '' }}" data-file-items>
                    @forelse ($files as $file)
                        <article class="file-tile" data-file-item>
                            @if ($imagePreviews && $file->is_image)
                                <a class="file-tile-preview" href="{{ route('files.preview', $file) }}" aria-label="Відкрити {{ $file->original_name }}">
                                    <img src="{{ route('files.inline', $file) }}" alt="{{ $file->original_name }}" loading="eager" decoding="async">
                                </a>
                            @elseif ($imagePreviews)
                                <div class="file-tile-preview file-tile-preview-empty" aria-hidden="true">
                                    <span>{{ $file->type_label }}</span>
                                </div>
                            @endif
                            <div class="file-tile-head">
                                <span class="file-icon">{{ $file->type_label }}</span>
                                <div class="file-tile-title">
                                    <strong title="{{ $file->original_name }}">{{ $file->original_name }}</strong>
                                    <span>{{ $file->mime_type ?? 'unknown' }}</span>
                                </div>
                            </div>
                            <div class="file-tile-meta">
                                <span>{{ $file->folder?->name ?? 'Без папки' }}</span>
                                <span>{{ $file->storage_label }}</span>
                                <span>{{ $file->human_size }} · {{ $file->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            <div class="file-tile-actions">
                                @include('files.partials.actions', ['file' => $file])
                            </div>
                        </article>
                    @empty
                        <div class="empty">У цьому розділі ще немає файлів. Додайте файл через форму завантаження.</div>
                    @endforelse
                </div>
            @else
                <div class="table-wrap">
                    <table class="compact-file-table">
                        <thead>
                            <tr>
                                <th>Файл</th>
                                <th>Папка</th>
                                <th>Сховище</th>
                                <th>Розмір</th>
                                <th>Дата</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody data-file-items>
                            @forelse ($files as $file)
                                <tr data-file-item>
                                    <td>
                                        <div class="file-table-name">
                                            <span class="file-icon">{{ $file->type_label }}</span>
                                            <div class="file-table-title">
                                                <strong title="{{ $file->original_name }}">{{ $file->original_name }}</strong>
                                                <span>{{ $file->mime_type ?? 'unknown' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="muted">{{ $file->folder?->name ?? 'Без папки' }}</td>
                                    <td class="muted">{{ $file->storage_label }}</td>
                                    <td>{{ $file->human_size }}</td>
                                    <td class="muted">{{ $file->created_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        <div class="file-row-actions">
                                            @include('files.partials.actions', ['file' => $file])
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty">У цьому розділі ще немає файлів. Додайте файл через форму завантаження.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($files->lastPage() > 1)
                @php
                    $current = $files->currentPage();
                    $last = $files->lastPage();
                    $window = 1;
                    $pages = [];
                    $pages[] = 1;
                    if ($current - $window > 2) {
                        $pages[] = '...';
                    }
                    for ($i = max(2, $current - $window); $i <= min($last - 1, $current + $window); $i++) {
                        $pages[] = $i;
                    }
                    if ($current + $window < $last - 1) {
                        $pages[] = '...';
                    }
                    if ($last > 1) {
                        $pages[] = $last;
                    }
                    $from = ($current - 1) * $files->perPage() + 1;
                    $to = min($current * $files->perPage(), $files->total());
                @endphp

                <nav class="pagination-v2" aria-label="Навігація сторінок" data-pagination>
                    <span class="pagination-summary">
                        {{ $from }}–{{ $to }} з {{ $files->total() }}
                    </span>

                    <div class="pagination-pages" role="group" aria-label="Сторінки">
                        <a
                            class="pagination-page pagination-arrow {{ $files->onFirstPage() ? 'is-disabled' : '' }}"
                            href="{{ $files->onFirstPage() ? '#' : $files->previousPageUrl() }}"
                            @if ($files->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
                            aria-label="Попередня сторінка"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="15 18 9 12 15 6"/>
                            </svg>
                        </a>

                        @foreach ($pages as $page)
                            @if ($page === '...')
                                <span class="pagination-ellipsis" aria-hidden="true">…</span>
                            @else
                                <a
                                    class="pagination-page {{ $page === $current ? 'is-active' : '' }}"
                                    href="{{ $files->url($page) }}"
                                    @if ($page === $current) aria-current="page" @endif
                                >{{ $page }}</a>
                            @endif
                        @endforeach

                        <a
                            class="pagination-page pagination-arrow {{ ! $files->hasMorePages() ? 'is-disabled' : '' }}"
                            href="{{ ! $files->hasMorePages() ? '#' : $files->nextPageUrl() }}"
                            @if (! $files->hasMorePages()) aria-disabled="true" tabindex="-1" @endif
                            aria-label="Наступна сторінка"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                    </div>

                    <form
                        class="pagination-jump"
                        action="{{ $files->path() }}"
                        method="get"
                        data-pagination-jump
                        data-base-url="{{ $files->url(1) }}"
                    >
                        <label for="pagination-page-input">Перейти на</label>
                        <input
                            id="pagination-page-input"
                            class="field"
                            type="number"
                            name="page"
                            min="1"
                            max="{{ $last }}"
                            value="{{ $current }}"
                            inputmode="numeric"
                        >
                        <button class="button secondary" type="submit">→</button>
                    </form>
                </nav>
            @endif
        </section>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            if (! document.querySelector('[data-file-items]')) {
                return;
            }

            initDropzone();

            window.addEventListener('popstate', () => {
                refreshFilesPage(window.location.href, false);
            });

            document.addEventListener('submit', async (event) => {
                const uploadForm = event.target.closest('[data-upload-form]');

                if (uploadForm) {
                    event.preventDefault();
                    uploadFiles(uploadForm);

                    return;
                }

                const filterForm = event.target.closest('[data-ajax-filter]');

                if (filterForm) {
                    event.preventDefault();

                    const url = new URL(filterForm.action, window.location.origin);
                    new FormData(filterForm).forEach((value, key) => {
                        if (value !== '') {
                            url.searchParams.set(key, value);
                        }
                    });
                    url.searchParams.delete('page');

                    refreshFilesPage(url.toString(), true, { region: 'files' });

                    return;
                }

                const jumpForm = event.target.closest('[data-pagination-jump]');

                if (jumpForm) {
                    event.preventDefault();

                    const baseUrl = jumpForm.dataset.baseUrl || jumpForm.action || window.location.href;
                    const pageInput = jumpForm.querySelector('input[name="page"]');
                    const target = parseInt(pageInput?.value || '1', 10);
                    const max = parseInt(pageInput?.max || '1', 10);
                    const page = Math.max(1, Math.min(max || 1, isNaN(target) ? 1 : target));

                    const url = new URL(baseUrl, window.location.origin);

                    new URL(window.location.href).searchParams.forEach((value, key) => {
                        if (key !== 'page' && value !== '') {
                            url.searchParams.set(key, value);
                        }
                    });

                    if (page > 1) {
                        url.searchParams.set('page', page);
                    } else {
                        url.searchParams.delete('page');
                    }

                    refreshFilesPage(url.toString(), true, { region: 'files' });

                    return;
                }

                const ajaxForm = event.target.closest('[data-ajax-form]');

                if (ajaxForm) {
                    event.preventDefault();
                    submitAjaxForm(ajaxForm);
                }
            });

            document.addEventListener('click', async (event) => {
                const actionTrigger = event.target.closest('.action-menu-trigger');

                if (actionTrigger) {
                    const currentMenu = actionTrigger.closest('[data-file-share]');

                    document.querySelectorAll('[data-file-share][open]').forEach((menu) => {
                        if (menu !== currentMenu) {
                            menu.removeAttribute('open');
                        }
                    });

                    requestAnimationFrame(() => {
                        if (currentMenu?.open) {
                            positionActionPanel(currentMenu, actionTrigger);
                        }
                    });

                    return;
                }

                const actionClose = event.target.closest('[data-action-close]');

                if (actionClose) {
                    event.preventDefault();
                    actionClose.closest('[data-file-share]')?.removeAttribute('open');

                    return;
                }

                const shareEnable = event.target.closest('[data-share-enable]');
                const shareDisable = event.target.closest('[data-share-disable]');
                const shareSave = event.target.closest('[data-share-save]');
                const shareCopy = event.target.closest('[data-share-copy]');

                if (shareEnable || shareDisable || shareSave || shareCopy) {
                    const panel = event.target.closest('[data-file-share]');

                    if (! panel) {
                        return;
                    }

                    event.preventDefault();

                    if (shareCopy) {
                        copyShareLink(panel);
                        return;
                    }

                    try {
                        const activeButton = shareEnable || shareDisable || shareSave;
                        setShareBusy(activeButton, true);

                        if (shareEnable) {
                            const data = await sendShareRequest(panel.dataset.shareUrl, 'POST');
                            updateSharePanel(panel, data.share, data.message);
                        } else if (shareDisable) {
                            const data = await sendShareRequest(panel.dataset.shareDisableUrl, 'DELETE');
                            updateSharePanel(panel, data.share, data.message);
                        } else if (shareSave) {
                            const data = await sendShareRequest(panel.dataset.shareSettingsUrl, 'PATCH', {
                                share_max_views: panel.querySelector('[data-share-max-views]')?.value || null,
                                share_expires_at: panel.querySelector('[data-share-expires-at]')?.value || null,
                            });
                            updateSharePanel(panel, data.share, data.message);
                        }
                    } catch (error) {
                        showShareMessage(panel, error.message || 'Не вдалося зберегти налаштування.', true);
                    } finally {
                        setShareBusy(shareEnable || shareDisable || shareSave, false);
                    }

                    return;
                }

                if (! event.target.closest('[data-file-share]')) {
                    document.querySelectorAll('[data-file-share][open]').forEach((menu) => {
                        menu.removeAttribute('open');
                    });
                }

                const paginationLink = event.target.closest('.pagination-page:not(.is-disabled)');

                if (paginationLink) {
                    event.preventDefault();

                    if (paginationLink.getAttribute('href') && paginationLink.getAttribute('href') !== '#') {
                        refreshFilesPage(paginationLink.href, true, { region: 'files' });
                    }

                    return;
                }

                const navigationLink = event.target.closest('.folder-item, .folder-link, .view-toggle a, .filter-reset');

                if (navigationLink && ! navigationLink.target) {
                    event.preventDefault();
                    refreshFilesPage(navigationLink.href, true, { region: 'files' });
                }
            });

            document.addEventListener('pointerdown', (event) => {
                const handle = event.target.closest('[data-action-drag-handle]');

                if (! handle || event.target.closest('[data-action-close]')) {
                    return;
                }

                const panel = handle.closest('.file-action-panel');

                if (! panel) {
                    return;
                }

                event.preventDefault();

                const rect = panel.getBoundingClientRect();
                const offsetX = event.clientX - rect.left;
                const offsetY = event.clientY - rect.top;

                const movePanel = (moveEvent) => {
                    moveEvent.preventDefault();

                    setPanelPosition(panel, moveEvent.clientX - offsetX, moveEvent.clientY - offsetY);
                };

                const stopDragging = () => {
                    document.removeEventListener('pointermove', movePanel);
                    document.removeEventListener('pointerup', stopDragging);
                    document.removeEventListener('pointercancel', stopDragging);
                };

                document.addEventListener('pointermove', movePanel);
                document.addEventListener('pointerup', stopDragging, { once: true });
                document.addEventListener('pointercancel', stopDragging, { once: true });
            });

            async function submitAjaxForm(form) {
                const submitter = form.querySelector('[type="submit"]');

                try {
                    setShareBusy(submitter, true);

                    const response = await fetch(form.action, {
                        method: (form.method || 'POST').toUpperCase(),
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                    });
                    const html = await response.text();

                    if (! response.ok) {
                        throw new Error(extractErrorFromHtml(html) || 'Дію не виконано.');
                    }

                    replaceFilesPageFromHtml(html, response.url || window.location.href, true);
                } catch (error) {
                    showPageFlash(error.message || 'Дію не виконано.', true);
                } finally {
                    setShareBusy(submitter, false);
                }
            }

            function initDropzone() {
                const dropzone = document.querySelector('[data-dropzone]');
                const input = document.querySelector('[data-upload-input]');

                if (! dropzone || ! input) {
                    return;
                }

                if (! dropzone.dataset.bound) {
                    dropzone.dataset.bound = '1';

                    ['dragenter', 'dragover'].forEach((type) => {
                        dropzone.addEventListener(type, (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            dropzone.classList.add('is-dragover');
                        });
                    });

                    ['dragleave', 'drop'].forEach((type) => {
                        dropzone.addEventListener(type, (event) => {
                            event.preventDefault();
                            event.stopPropagation();

                            if (type === 'dragleave' && dropzone.contains(event.relatedTarget)) {
                                return;
                            }

                            dropzone.classList.remove('is-dragover');
                        });
                    });

                    dropzone.addEventListener('drop', (event) => {
                        const dropped = event.dataTransfer?.files;

                        if (! dropped?.length) {
                            return;
                        }

                        const transfer = new DataTransfer();

                        Array.from(input.files || []).forEach((existing) => transfer.items.add(existing));
                        Array.from(dropped).forEach((file) => transfer.items.add(file));

                        input.files = transfer.files;
                        renderSelectedFiles();
                    });

                    input.addEventListener('change', renderSelectedFiles);
                }

                document.addEventListener('click', handleSelectedListClick);
                renderSelectedFiles();
                initUploadControls();
                initFlatpickr();
                initFoldersAddToggle();
            }

            function initFoldersAddToggle() {
                if (document.body.dataset.foldersToggleBound) return;
                document.body.dataset.foldersToggleBound = '1';

                document.addEventListener('click', (event) => {
                    const toggle = event.target.closest('[data-folders-add-toggle]');

                    if (toggle) {
                        event.preventDefault();
                        const form = document.querySelector('[data-folders-form]');

                        if (form) {
                            const isOpen = ! form.hidden;
                            form.hidden = isOpen;
                            toggle.classList.toggle('is-active', ! isOpen);
                            if (! isOpen) {
                                form.querySelector('[data-folders-form-input]')?.focus();
                            }
                        }

                        return;
                    }

                    const cancel = event.target.closest('[data-folders-form-cancel]');

                    if (cancel) {
                        event.preventDefault();
                        const form = document.querySelector('[data-folders-form]');
                        const tog = document.querySelector('[data-folders-add-toggle]');
                        if (form) form.hidden = true;
                        if (tog) tog.classList.remove('is-active');
                        return;
                    }
                });
            }

            function initFlatpickr() {
                if (typeof window.flatpickr !== 'function') {
                    window.setTimeout(initFlatpickr, 200);
                    return;
                }

                document.querySelectorAll('[data-flatpickr-range]').forEach((input) => {
                    if (input._flatpickr) {
                        input._flatpickr.destroy();
                    }

                    const wrapper = input.closest('.filter-daterange');
                    const fromInput = wrapper?.querySelector('[data-flatpickr-from]');
                    const toInput = wrapper?.querySelector('[data-flatpickr-to]');

                    const initial = [];

                    if (input.dataset.initialFrom) initial.push(input.dataset.initialFrom);
                    if (input.dataset.initialTo) initial.push(input.dataset.initialTo);

                    window.flatpickr(input, {
                        mode: 'range',
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'd.m.Y',
                        locale: window.flatpickr?.l10ns?.uk || 'default',
                        maxDate: 'today',
                        defaultDate: initial.length ? initial : null,
                        showMonths: window.matchMedia('(min-width: 720px)').matches ? 2 : 1,
                        onChange: (dates) => {
                            const fmt = (d) => {
                                if (! d) return '';
                                const y = d.getFullYear();
                                const m = String(d.getMonth() + 1).padStart(2, '0');
                                const day = String(d.getDate()).padStart(2, '0');
                                return `${y}-${m}-${day}`;
                            };

                            if (fromInput) fromInput.value = fmt(dates[0]);
                            if (toInput) toInput.value = fmt(dates[1] || dates[0]);
                        },
                    });
                });
            }

            function initUploadControls() {
                if (! document.body.dataset.uploadDropdownBound) {
                    document.body.dataset.uploadDropdownBound = '1';

                    document.addEventListener('click', (event) => {
                        const trigger = event.target.closest('[data-upload-dropdown-trigger]');

                        if (trigger) {
                            event.preventDefault();
                            const dropdown = trigger.closest('[data-upload-dropdown]');
                            const isOpen = dropdown.classList.contains('is-open');
                            closeAllUploadDropdowns();
                            if (! isOpen) openUploadDropdown(dropdown);
                            return;
                        }

                        const option = event.target.closest('[data-upload-dropdown-option]');

                        if (option) {
                            event.preventDefault();
                            selectUploadDropdownOption(option);
                            return;
                        }

                        if (! event.target.closest('[data-upload-dropdown]')) {
                            closeAllUploadDropdowns();
                        }
                    });

                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') closeAllUploadDropdowns();
                    });
                }
            }

            function openUploadDropdown(dropdown) {
                dropdown.classList.add('is-open');
                const trigger = dropdown.querySelector('[data-upload-dropdown-trigger]');
                const menu = dropdown.querySelector('[data-upload-dropdown-menu]');
                if (trigger) trigger.setAttribute('aria-expanded', 'true');
                if (menu) menu.hidden = false;
            }

            function closeAllUploadDropdowns() {
                document.querySelectorAll('[data-upload-dropdown].is-open').forEach((dropdown) => {
                    dropdown.classList.remove('is-open');
                    const trigger = dropdown.querySelector('[data-upload-dropdown-trigger]');
                    const menu = dropdown.querySelector('[data-upload-dropdown-menu]');
                    if (trigger) trigger.setAttribute('aria-expanded', 'false');
                    if (menu) menu.hidden = true;
                });
            }

            function selectUploadDropdownOption(option) {
                const dropdown = option.closest('[data-upload-dropdown]');
                if (! dropdown) return;

                const value = option.dataset.value;
                const label = option.querySelector('strong')?.textContent || '';

                const input = dropdown.querySelector('[data-upload-dropdown-input]');
                const valueSpan = dropdown.querySelector('[data-upload-dropdown-value]');

                if (input) input.value = value;
                if (valueSpan) valueSpan.textContent = label;

                dropdown.querySelectorAll('[data-upload-dropdown-option]').forEach((opt) => {
                    opt.classList.toggle('is-selected', opt === option);
                    opt.setAttribute('aria-selected', opt === option ? 'true' : 'false');
                });

                closeAllUploadDropdowns();
            }

            function handleSelectedListClick(event) {
                const removeBtn = event.target.closest('[data-upload-remove]');

                if (removeBtn) {
                    event.preventDefault();
                    removeFileFromInput(parseInt(removeBtn.dataset.uploadRemove, 10));
                    return;
                }

                const clearBtn = event.target.closest('[data-upload-clear]');

                if (clearBtn) {
                    event.preventDefault();
                    const input = document.querySelector('[data-upload-input]');

                    if (input) {
                        input.value = '';
                        renderSelectedFiles();
                    }
                }
            }

            function removeFileFromInput(index) {
                const input = document.querySelector('[data-upload-input]');

                if (! input?.files) {
                    return;
                }

                const transfer = new DataTransfer();

                Array.from(input.files).forEach((file, fileIndex) => {
                    if (fileIndex !== index) {
                        transfer.items.add(file);
                    }
                });

                input.files = transfer.files;
                renderSelectedFiles();
            }

            function renderSelectedFiles() {
                const input = document.querySelector('[data-upload-input]');
                const wrapper = document.querySelector('[data-upload-selected]');
                const list = document.querySelector('[data-upload-list]');
                const countLabel = document.querySelector('[data-upload-count]');
                const totalLabel = document.querySelector('[data-upload-total-size]');
                const submitLabel = document.querySelector('[data-upload-submit-label]');

                if (! input || ! wrapper || ! list) {
                    return;
                }

                const files = Array.from(input.files || []);
                const form = document.querySelector('[data-upload-form]');

                if (files.length === 0) {
                    wrapper.hidden = true;
                    list.innerHTML = '';

                    if (submitLabel) {
                        submitLabel.textContent = 'Завантажити';
                    }

                    if (form) form.classList.remove('has-files');

                    return;
                }

                if (form) form.classList.add('has-files');

                wrapper.hidden = false;
                list.innerHTML = '';

                let totalSize = 0;

                files.forEach((file, index) => {
                    totalSize += file.size;

                    const item = document.createElement('li');
                    item.className = 'upload-selected-item';
                    item.dataset.uploadItem = index;
                    item.dataset.state = 'idle';
                    item.innerHTML = `
                        <span class="upload-selected-icon">${escapeHtml(fileBadge(file))}</span>
                        <span class="upload-selected-name">
                            <strong title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</strong>
                            <span class="upload-selected-status" data-upload-status>${escapeHtml(file.type || 'unknown')}</span>
                        </span>
                        <span class="upload-selected-size">${escapeHtml(formatBytes(file.size))}</span>
                        <span class="upload-selected-state" data-upload-state aria-hidden="true">
                            <svg class="upload-state-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 3a9 9 0 1 0 9 9"/>
                            </svg>
                            <svg class="upload-state-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="5 12 10 17 19 7"/>
                            </svg>
                            <svg class="upload-state-error" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                <line x1="6" y1="6" x2="18" y2="18"/>
                                <line x1="18" y1="6" x2="6" y2="18"/>
                            </svg>
                        </span>
                        <button type="button" class="upload-selected-remove" data-upload-remove="${index}" aria-label="Видалити ${escapeHtml(file.name)}">×</button>
                        <span class="upload-item-progress" data-upload-item-progress>
                            <span class="upload-item-progress-bar" data-upload-item-bar></span>
                        </span>
                    `;
                    list.appendChild(item);
                });

                if (countLabel) {
                    countLabel.textContent = files.length;
                }

                if (totalLabel) {
                    totalLabel.textContent = formatBytes(totalSize);
                }

                if (submitLabel) {
                    submitLabel.textContent = files.length === 1
                        ? 'Завантажити 1 файл'
                        : `Завантажити ${files.length} файли`;
                }
            }

            function fileBadge(file) {
                const type = (file.type || '').toLowerCase();
                const name = (file.name || '').toLowerCase();
                const ext = name.includes('.') ? name.split('.').pop() : '';

                if (type.startsWith('image/')) return 'IMG';
                if (type.startsWith('video/') || ['mp4', 'mov', 'avi', 'mkv', 'webm'].includes(ext)) return 'VID';
                if (type.startsWith('audio/') || ['mp3', 'ogg', 'wav', 'm4a'].includes(ext)) return 'AUD';
                if (['pdf'].includes(ext)) return 'PDF';
                if (['doc', 'docx', 'odt', 'rtf'].includes(ext)) return 'DOC';
                if (['xls', 'xlsx', 'csv'].includes(ext)) return 'XLS';
                if (['ppt', 'pptx'].includes(ext)) return 'PPT';
                if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return 'ZIP';
                if (['txt', 'md', 'log'].includes(ext)) return 'TXT';

                return ext ? ext.slice(0, 4).toUpperCase() : 'FILE';
            }

            function formatBytes(bytes) {
                if (! Number.isFinite(bytes) || bytes <= 0) {
                    return '0 B';
                }

                const units = ['B', 'KB', 'MB', 'GB'];
                let value = bytes;
                let unit = 0;

                while (value >= 1024 && unit < units.length - 1) {
                    value /= 1024;
                    unit++;
                }

                return `${value < 10 && unit > 0 ? value.toFixed(1) : Math.round(value)} ${units[unit]}`;
            }

            async function uploadFiles(form) {
                const fileInput = form.querySelector('input[type="file"]');
                const files = Array.from(fileInput?.files || []);

                if (! files.length) {
                    showPageFlash('Оберіть хоча б один файл для завантаження.', true);
                    return;
                }

                const submitter = form.querySelector('[type="submit"]');
                const folderId = form.querySelector('[name="folder_id"]')?.value || '';
                const storageId = form.querySelector('[name="telegram_storage_group_id"]')?.value || '';
                const csrfToken = form.querySelector('[name="_token"]')?.value || '';

                setShareBusy(submitter, true);
                form.classList.add('is-uploading');

                files.forEach((_, index) => setUploadItemState(index, 'queued'));
                setUploadProgress(form, 0, `Очікування... 0 / ${files.length}`);

                let succeeded = 0;
                let failed = 0;
                let lastResponseHtml = null;
                let lastResponseUrl = null;
                const failures = [];

                for (let i = 0; i < files.length; i++) {
                    setUploadProgress(
                        form,
                        Math.round((i / files.length) * 100),
                        `Завантаження ${i + 1} з ${files.length}: ${files[i].name}`
                    );

                    try {
                        const result = await uploadSingleFile(form.action, files[i], i, {
                            folderId,
                            storageId,
                            csrfToken,
                        });

                        succeeded++;
                        lastResponseHtml = result.html;
                        lastResponseUrl = result.url;
                        setUploadItemState(i, 'done');
                    } catch (error) {
                        failed++;
                        failures.push(`${files[i].name}: ${error.message}`);
                        setUploadItemState(i, 'error', 0, error.message);
                    }
                }

                setShareBusy(submitter, false);
                form.classList.remove('is-uploading');

                const finalLabel = failed === 0
                    ? `Готово. Завантажено ${succeeded} ${pluralFiles(succeeded)}.`
                    : `Завершено: ${succeeded} з ${files.length}. Помилок: ${failed}.`;

                setUploadProgress(form, 100, finalLabel);

                if (failed > 0) {
                    showPageFlash(failures.slice(0, 3).join('; ') + (failures.length > 3 ? '...' : ''), true);
                }

                if (succeeded > 0) {
                    window.setTimeout(() => {
                        if (lastResponseHtml) {
                            replaceFilesPageFromHtml(lastResponseHtml, lastResponseUrl || window.location.href, true);
                        } else {
                            refreshFilesPage(window.location.href, false);
                        }
                    }, 600);
                }
            }

            function uploadSingleFile(action, file, index, options) {
                return new Promise((resolve, reject) => {
                    const formData = new FormData();

                    formData.append('files[]', file);
                    formData.append('_token', options.csrfToken);

                    if (options.folderId) {
                        formData.append('folder_id', options.folderId);
                    }

                    if (options.storageId) {
                        formData.append('telegram_storage_group_id', options.storageId);
                    }

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', action);
                    xhr.setRequestHeader('Accept', 'text/html');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    setUploadItemState(index, 'uploading', 0);

                    xhr.upload.addEventListener('progress', (event) => {
                        if (! event.lengthComputable) {
                            return;
                        }

                        const percent = Math.max(1, Math.min(95, (event.loaded / event.total) * 95));
                        setUploadItemState(index, 'uploading', percent);
                    });

                    xhr.upload.addEventListener('load', () => {
                        setUploadItemState(index, 'processing', 98);
                    });

                    xhr.onload = () => {
                        if (xhr.status < 200 || xhr.status >= 300) {
                            const error = extractErrorFromHtml(xhr.responseText) || `HTTP ${xhr.status}`;
                            reject(new Error(error));
                            return;
                        }

                        resolve({
                            html: xhr.responseText,
                            url: xhr.responseURL,
                        });
                    };

                    xhr.onerror = () => reject(new Error('Помилка мережі'));
                    xhr.ontimeout = () => reject(new Error('Перевищено час очікування'));

                    xhr.send(formData);
                });
            }

            function setUploadItemState(index, state, percent, statusText) {
                const item = document.querySelector(`[data-upload-item="${index}"]`);

                if (! item) {
                    return;
                }

                item.dataset.state = state;

                const bar = item.querySelector('[data-upload-item-bar]');
                const status = item.querySelector('[data-upload-status]');

                if (bar) {
                    if (state === 'done') {
                        bar.style.width = '100%';
                    } else if (state === 'error' || state === 'idle' || state === 'queued') {
                        bar.style.width = state === 'queued' ? '0%' : (state === 'error' ? '100%' : '0%');
                    } else if (typeof percent === 'number') {
                        bar.style.width = `${Math.max(0, Math.min(100, percent))}%`;
                    }
                }

                if (status) {
                    const labels = {
                        idle: '',
                        queued: 'У черзі',
                        uploading: typeof percent === 'number' ? `Завантаження ${Math.round(percent)}%` : 'Завантаження...',
                        processing: 'Обробка на сервері...',
                        done: 'Готово ✓',
                        error: statusText || 'Помилка завантаження',
                    };

                    if (labels[state] !== undefined) {
                        status.textContent = labels[state];
                    }
                }
            }

            function pluralFiles(count) {
                const mod10 = count % 10;
                const mod100 = count % 100;

                if (mod10 === 1 && mod100 !== 11) return 'файл';
                if ([2, 3, 4].includes(mod10) && ![12, 13, 14].includes(mod100)) return 'файли';

                return 'файлів';
            }

            async function refreshFilesPage(url, pushHistory, options = {}) {
                const region = options.region || null;
                const filesRegion = document.querySelector('[data-files-region]');

                if (region === 'files' && filesRegion) {
                    filesRegion.classList.add('is-loading');
                }

                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const html = await response.text();

                    if (! response.ok) {
                        throw new Error(extractErrorFromHtml(html) || 'Не вдалося оновити сторінку.');
                    }

                    replaceFilesPageFromHtml(html, response.url || url, pushHistory, options);
                } catch (error) {
                    showPageFlash(error.message || 'Не вдалося оновити сторінку.', true);
                } finally {
                    document.querySelector('[data-files-region]')?.classList.remove('is-loading');
                }
            }

            function replaceFilesPageFromHtml(html, url, pushHistory, options = {}) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const region = options.region || null;

                let selectors;

                if (region === 'files') {
                    // Filter / pagination — only refresh the file list region
                    selectors = ['[data-flash-area]', '[data-files-region]'];

                    // Sync active state on folder list without re-fetching it
                    const nextActive = doc.querySelector('.folder-item.is-active');
                    const nextHref = nextActive?.getAttribute('href');
                    document.querySelectorAll('.folder-item').forEach((item) => {
                        const isActive = nextHref && item.getAttribute('href') === nextHref;
                        item.classList.toggle('is-active', !! isActive);
                    });
                } else {
                    // Full swap (after upload, etc.)
                    selectors = ['[data-flash-area]', '.upload-panel', '.workspace'];
                }

                selectors.forEach((selector) => {
                    const current = document.querySelector(selector);
                    const next = doc.querySelector(selector);

                    if (current && next) {
                        current.replaceWith(next);
                    }
                });

                document.querySelectorAll('[data-file-share][open]').forEach((menu) => {
                    menu.removeAttribute('open');
                });

                if (pushHistory && url && url !== window.location.href) {
                    window.history.pushState({}, '', url);
                }

                initDropzone();
            }

            function setUploadProgress(form, percent, label) {
                const progress = form.querySelector('[data-upload-progress]');

                if (! progress) {
                    return;
                }

                const normalized = Math.max(0, Math.min(100, Math.round(percent)));
                const bar = progress.querySelector('[data-upload-progress-bar]');
                const percentLabel = progress.querySelector('[data-upload-progress-percent]');
                const textLabel = progress.querySelector('[data-upload-progress-label]');

                progress.hidden = false;

                if (bar) {
                    bar.style.width = `${normalized}%`;
                }

                if (percentLabel) {
                    percentLabel.textContent = `${normalized}%`;
                }

                if (textLabel) {
                    textLabel.textContent = label;
                }
            }

            function extractErrorFromHtml(html) {
                const doc = new DOMParser().parseFromString(html || '', 'text/html');
                const errorItems = Array.from(doc.querySelectorAll('.errors li')).map((item) => item.textContent.trim());

                return errorItems[0] || doc.querySelector('.errors')?.textContent.trim() || '';
            }

            function showPageFlash(message, isError) {
                let flash = document.querySelector('[data-flash-area]');

                if (! flash) {
                    flash = document.createElement('div');
                    flash.dataset.flashArea = '';
                    document.querySelector('.topbar')?.after(flash);
                }

                flash.innerHTML = isError
                    ? `<div class="errors"><strong>Перевірте дію.</strong><ul><li>${escapeHtml(message)}</li></ul></div>`
                    : `<div class="status">${escapeHtml(message)}</div>`;
            }

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value;

                return div.innerHTML;
            }

            async function sendShareRequest(url, method, payload = null) {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: payload ? JSON.stringify(payload) : null,
                });
                const data = await response.json().catch(() => ({}));

                if (! response.ok) {
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(firstError || data.message || 'Запит не виконано.');
                }

                return data;
            }

            function positionActionPanel(menu, trigger) {
                const panel = menu.querySelector('.file-action-panel');

                if (! panel || ! trigger) {
                    return;
                }

                panel.style.removeProperty('--action-panel-left');
                panel.style.removeProperty('--action-panel-top');

                const triggerRect = trigger.getBoundingClientRect();
                const panelRect = panel.getBoundingClientRect();
                const gap = 8;
                let left = triggerRect.right - panelRect.width;
                let top = triggerRect.bottom + gap;

                if (top + panelRect.height > window.innerHeight - gap) {
                    top = triggerRect.top - panelRect.height - gap;
                }

                setPanelPosition(panel, left, top);
            }

            function setPanelPosition(panel, left, top) {
                const margin = 12;
                const rect = panel.getBoundingClientRect();
                const width = rect.width || Math.min(390, window.innerWidth - margin * 2);
                const height = rect.height || Math.min(560, window.innerHeight - margin * 2);
                const maxLeft = Math.max(margin, window.innerWidth - width - margin);
                const maxTop = Math.max(margin, window.innerHeight - height - margin);

                panel.style.setProperty('--action-panel-left', `${Math.min(Math.max(left, margin), maxLeft)}px`);
                panel.style.setProperty('--action-panel-top', `${Math.min(Math.max(top, margin), maxTop)}px`);
            }

            function updateSharePanel(panel, share, message) {
                const enabled = Boolean(share?.is_enabled);
                const enabledBlock = panel.querySelector('[data-share-enabled]');
                const disabledBlock = panel.querySelector('[data-share-disabled]');
                const status = panel.querySelector('[data-share-status]');
                const linkInput = panel.querySelector('[data-share-link-input]');
                const openLink = panel.querySelector('[data-share-open]');
                const maxViews = panel.querySelector('[data-share-max-views]');
                const expiresAt = panel.querySelector('[data-share-expires-at]');
                const usage = panel.querySelector('[data-share-usage]');

                panel.querySelector('.share-settings')?.classList.toggle('is-enabled', enabled);

                if (enabledBlock) {
                    enabledBlock.hidden = ! enabled;
                }

                if (disabledBlock) {
                    disabledBlock.hidden = enabled;
                }

                if (status) {
                    status.textContent = share?.status_label || (enabled ? 'Активний' : 'Вимкнено');
                }

                if (linkInput) {
                    linkInput.value = share?.url || '';
                }

                if (openLink) {
                    openLink.href = share?.url || '#';
                    openLink.toggleAttribute('aria-disabled', ! share?.url);
                }

                if (maxViews) {
                    maxViews.value = share?.share_max_views ?? '';
                }

                if (expiresAt) {
                    expiresAt.value = share?.share_expires_at_input || '';
                }

                if (usage) {
                    usage.textContent = share?.usage_label || 'Переглядів: 0 / без ліміту · Доступний до: без дати';
                }

                showShareMessage(panel, message || 'Збережено.', false);
            }

            function copyShareLink(panel) {
                const input = panel.querySelector('[data-share-link-input]');

                if (! input?.value) {
                    showShareMessage(panel, 'Спочатку створіть публічний лінк.', true);
                    return;
                }

                if (navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText(input.value)
                        .then(() => showShareMessage(panel, 'Лінк скопійовано.', false))
                        .catch(() => fallbackCopy(input, panel));
                    return;
                }

                fallbackCopy(input, panel);
            }

            function fallbackCopy(input, panel) {
                input.focus();
                input.select();
                document.execCommand('copy');
                showShareMessage(panel, 'Лінк скопійовано.', false);
            }

            function showShareMessage(panel, message, isError) {
                const target = panel.querySelector('[data-share-message]');

                if (! target) {
                    return;
                }

                target.textContent = message;
                target.classList.toggle('is-error', isError);
            }

            function setShareBusy(target, isBusy) {
                if (! target) {
                    return;
                }

                target.toggleAttribute('disabled', isBusy);
                target.setAttribute('aria-busy', isBusy ? 'true' : 'false');
            }
        })();
    </script>
@endpush
