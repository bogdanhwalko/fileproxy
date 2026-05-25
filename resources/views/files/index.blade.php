@extends('layouts.site')

@section('title', 'Файли — FileProxy')
@section('robots', 'noindex, nofollow')

@push('head')
    <link rel="stylesheet" href="@vasset('css/uploader.css')">
@endpush

@section('content')
    <x-app-topbar title="FileProxy" subtitle="Завантаження, папки і швидке керування файлами" />


    <div data-flash-area>
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
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

        <form class="upload-form-v2" action="{{ route('files.store') }}" method="post" enctype="multipart/form-data" data-upload-form data-fp-uploader data-status-url="{{ route('files.status', ['file' => '__id__']) }}" data-reload-url="{{ url()->full() }}" data-max-file-mb="{{ $telegramUploadMaxMb }}" data-max-protected-mb="{{ $protectedUploadMaxMb }}">
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
                    $oldStorageId = (string) old('telegram_storage_group_id', '');

                    if ($canUseLocalStorage) {
                        $storageOptions->push([
                            'value' => '',
                            'label' => 'Локальне сховище',
                            'sublabel' => 'Файли на сервері',
                            'icon' => 'server',
                            'is_selected' => $oldStorageId === '',
                        ]);
                    }

                    foreach ($telegramStorageGroups as $storageGroup) {
                        $isThisSelected = $oldStorageId !== ''
                            ? $oldStorageId === (string) $storageGroup->id
                            : (! $canUseLocalStorage && (bool) $storageGroup->is_default);

                        $storageOptions->push([
                            'value' => (string) $storageGroup->id,
                            'label' => $storageGroup->title,
                            'sublabel' => '@'.($storageGroup->botToken?->username ?: $storageGroup->botToken?->name ?? 'бот'),
                            'icon' => 'tg',
                            'is_selected' => $isThisSelected,
                        ]);
                    }

                    if ($telegramStorageGroups->isEmpty() && ! $canUseLocalStorage) {
                        if ($systemTelegramStorageAvailable) {
                            $storageOptions->push([
                                'value' => '',
                                'label' => 'Системне Telegram-сховище',
                                'sublabel' => "Залишилось {$systemTelegramRemainingUploads} з {$systemTelegramUploadLimit}",
                                'icon' => 'tg',
                                'is_selected' => true,
                            ]);
                        } else {
                            $storageOptions->push([
                                'value' => '',
                                'label' => 'Telegram-сховище не налаштоване',
                                'sublabel' => 'Підключіть бота і групу в налаштуваннях',
                                'icon' => 'warn',
                                'is_selected' => true,
                            ]);
                        }
                    }

                    if (! $storageOptions->firstWhere('is_selected', true) && $storageOptions->isNotEmpty()) {
                        $storageOptions[0]['is_selected'] = true;
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

            <div class="upload-tags" data-upload-tags-container>
                <span class="upload-tags-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                        <line x1="7" y1="7" x2="7.01" y2="7"/>
                    </svg>
                </span>
                <span class="upload-tags-label-text">Теги</span>
                <div class="upload-tags-field" data-upload-tags-chips>
                    <input
                        type="text"
                        class="upload-tags-typing"
                        data-upload-tags-typing
                        maxlength="64"
                        placeholder="додати тег і Enter"
                        autocomplete="off"
                    >
                </div>
                <button type="button" class="upload-tags-hint-toggle" data-upload-tags-hint
                    title="Розділяй комою або Enter. Для незахищених файлів теги додаються як #hashtag у Telegram caption.">i</button>
                <input type="hidden" name="tags" value="" data-upload-tags>
            </div>
            <label class="upload-protect-toggle" data-upload-protect>
                <input type="checkbox" name="is_protected" value="1" data-upload-protect-checkbox>
                <span class="upload-protect-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </span>
                <span class="upload-protect-body">
                    <strong>Захистити файл</strong>
                    <span>Розбити на зашифровані частини, розкидати по групах. Максимум <strong>{{ $protectedUploadMaxMb }} MB</strong>. Працює тільки з вибраною своєю Telegram-групою.</span>
                </span>
            </label>

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

                    <div class="folder-color-picker" role="radiogroup" aria-label="Колір папки">
                        <label class="folder-color-swatch folder-color-swatch-none">
                            <input type="radio" name="color" value="" checked>
                            <span aria-hidden="true">⌀</span>
                        </label>
                        @foreach (\App\Models\FileFolder::COLOR_PALETTE as $colorKey => $colorHex)
                            <label class="folder-color-swatch" style="--swatch:{{ $colorHex }}" title="{{ ucfirst($colorKey) }}">
                                <input type="radio" name="color" value="{{ $colorKey }}">
                                <span aria-hidden="true"></span>
                            </label>
                        @endforeach
                    </div>

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
                        @php $folderColor = $folder->color && isset(\App\Models\FileFolder::COLOR_PALETTE[$folder->color]) ? \App\Models\FileFolder::COLOR_PALETTE[$folder->color] : null; @endphp
                        <div class="folder-row-v2 {{ $folderColor ? 'folder-row-colored' : '' }}" @if ($folderColor) style="--folder-color:{{ $folderColor }}" @endif>
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

                @if ($tags->isNotEmpty())
                    <header class="folders-header-v2 folders-header-tags">
                        <div class="folders-header-text">
                            <strong>Теги</strong>
                            <span class="folders-header-count">{{ $tags->count() }}</span>
                        </div>
                    </header>
                    <nav class="tag-list" aria-label="Список тегів">
                        @if ($activeTag)
                            <a class="tag-chip tag-chip-clear" href="{{ route('files.index', array_filter(['folder' => $folderFilter !== 'all' ? $folderFilter : null, 'view' => $display])) }}">
                                ✕ Скинути тег
                            </a>
                        @endif
                        @foreach ($tags as $tag)
                            <a
                                class="tag-chip {{ $activeTag?->id === $tag->id ? 'is-active' : '' }}"
                                href="{{ route('files.index', array_filter(['folder' => $folderFilter !== 'all' ? $folderFilter : null, 'view' => $display, 'tag' => $tag->name])) }}"
                            >
                                #{{ $tag->name }}
                                <span class="tag-chip-count">{{ $tag->files_count }}</span>
                            </a>
                        @endforeach
                    </nav>
                @endif
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
                        <article class="file-tile file-tile-status-{{ $file->status }}" data-file-item data-file-id="{{ $file->id }}">
                            <label class="fp-select-checkbox fp-select-checkbox-tile" title="Вибрати файл">
                                <input type="checkbox" data-fp-select aria-label="Вибрати {{ $file->original_name }}">
                                <span class="fp-select-mark" aria-hidden="true">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                            </label>
                            @if ($imagePreviews && $file->is_uploaded && $file->is_image && ! $file->is_protected)
                                <a class="file-tile-preview" href="{{ route('files.preview', $file) }}" aria-label="Відкрити {{ $file->original_name }}">
                                    <img
                                        src="{{ route('files.inline', $file) }}"
                                        alt="{{ $file->original_name }}"
                                        loading="lazy"
                                        decoding="async"
                                        data-preview-img
                                        data-type-label="{{ $file->type_label }}"
                                    >
                                </a>
                            @elseif ($imagePreviews)
                                <div class="file-tile-preview file-tile-preview-empty" aria-hidden="true">
                                    @if ($file->is_protected)
                                        <span title="Захищений файл — превʼю недоступне">🔒</span>
                                    @else
                                        <span>{{ $file->type_label }}</span>
                                    @endif
                                </div>
                            @endif
                            <div class="file-tile-head">
                                <span class="file-icon file-icon-cat-{{ $file->type_category }}">{{ $file->type_label }}</span>
                                <div class="file-tile-title">
                                    <strong title="{{ $file->original_name }}">
                                        @if ($file->is_protected)
                                            <span class="file-protected-badge" title="Захищений: розбито на зашифровані частини">🔒</span>
                                        @endif
                                        {{ $file->original_name }}
                                    </strong>
                                    <span>{{ $file->mime_type ?? 'unknown' }}</span>
                                </div>
                            </div>
                            @if (! $file->is_uploaded)
                                <span class="file-status-badge file-status-badge-{{ $file->status }}">{{ $file->status_label }}</span>
                            @endif
                            <div class="file-tile-meta">
                                <span>{{ $file->folder?->name ?? 'Без папки' }}</span>
                                <span>{{ $file->storage_label }}</span>
                                <span>{{ $file->human_size }} · @reltime($file->created_at)</span>
                            </div>
                            <div class="file-tile-actions">
                                @include('files.partials.actions', ['file' => $file])
                            </div>
                        </article>
                    @empty
                        @include('files.partials.empty-state')
                    @endforelse
                </div>
            @else
                <div class="table-wrap">
                    <table class="compact-file-table">
                        <thead>
                            <tr>
                                <th class="fp-select-cell">
                                    <label class="fp-select-checkbox" title="Вибрати все на сторінці">
                                        <input type="checkbox" data-fp-select-all aria-label="Вибрати все на сторінці">
                                        <span class="fp-select-mark" aria-hidden="true">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </span>
                                    </label>
                                </th>
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
                                <tr class="file-row-status-{{ $file->status }}" data-file-item data-file-id="{{ $file->id }}">
                                    <td class="fp-select-cell">
                                        <label class="fp-select-checkbox" title="Вибрати файл">
                                            <input type="checkbox" data-fp-select aria-label="Вибрати {{ $file->original_name }}">
                                            <span class="fp-select-mark" aria-hidden="true">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            </span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="file-table-name">
                                            <span class="file-icon file-icon-cat-{{ $file->type_category }}">{{ $file->type_label }}</span>
                                            <div class="file-table-title">
                                                <strong title="{{ $file->original_name }}">
                                                    @if ($file->is_protected)
                                                        <span class="file-protected-badge" title="Захищений: розбито на зашифровані частини">🔒</span>
                                                    @endif
                                                    {{ $file->original_name }}
                                                </strong>
                                                <span>{{ $file->mime_type ?? 'unknown' }}</span>
                                                @if (! $file->is_uploaded)
                                                    <span class="file-status-badge file-status-badge-{{ $file->status }}"
                                                    @if ($file->is_failed && $file->upload_failure_reason) title="{{ $file->upload_failure_reason }}" @endif>
                                                    {{ $file->status_label }}@if ($file->is_failed) <span class="file-status-info" aria-hidden="true">i</span>@endif
                                                </span>
                                                @endif
                                                @if ($file->tags->isNotEmpty())
                                                    <div class="file-tags-inline">
                                                        @foreach ($file->tags as $tag)
                                                            <a class="tag-chip tag-chip-inline" href="{{ route('files.index', array_filter(['folder' => $folderFilter !== 'all' ? $folderFilter : null, 'view' => $display, 'tag' => $tag->name])) }}">#{{ $tag->name }}</a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="muted">{{ $file->folder?->name ?? 'Без папки' }}</td>
                                    <td class="muted">{{ $file->storage_label }}</td>
                                    <td>{{ $file->human_size }}</td>
                                    <td class="muted">@reltime($file->created_at)</td>
                                    <td>
                                        <div class="file-row-actions">
                                            @include('files.partials.actions', ['file' => $file])
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        @include('files.partials.empty-state')
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

    {{-- Bulk selection action bar --}}
    <div class="fp-bulk-bar" data-fp-bulk-bar hidden role="region" aria-label="Дії над вибраними файлами">
        <div class="fp-bulk-bar-inner">
            <div class="fp-bulk-bar-count">
                <span class="fp-bulk-bar-count-pill" data-fp-bulk-count>0</span>
                <span class="fp-bulk-bar-count-label">вибрано</span>
            </div>

            <div class="fp-bulk-bar-actions">
                <details class="fp-bulk-move" data-fp-bulk-move>
                    <summary class="button secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                        <span>Перемістити в…</span>
                    </summary>
                    <div class="fp-bulk-move-menu">
                        <button type="button" class="fp-bulk-move-option" data-fp-bulk-move-folder="">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 12h18M3 6h18M3 18h18"/>
                            </svg>
                            <em>Корінь</em>
                        </button>
                        @foreach ($folders as $folder)
                            <button type="button" class="fp-bulk-move-option" data-fp-bulk-move-folder="{{ $folder->id }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                </svg>
                                <span>{{ $folder->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </details>

                <button type="button" class="button danger" data-fp-bulk-delete>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                    </svg>
                    <span>Видалити</span>
                </button>

                <button type="button" class="fp-bulk-bar-clear" data-fp-bulk-clear aria-label="Зняти вибір">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Sticky upload widget --}}
    <aside class="fp-up-widget" data-fp-uploader-widget hidden role="status" aria-live="polite">
        <header class="fp-up-head">
            <span class="fp-up-head-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
            </span>
            <span class="fp-up-head-title" data-fp-uploader-summary>Завантаження…</span>
            <button type="button" class="fp-up-head-btn" data-fp-uploader-minimize aria-label="Згорнути">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>
            <button type="button" class="fp-up-head-btn" data-fp-uploader-close aria-label="Закрити">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </header>
        <div class="fp-up-list" data-fp-uploader-list></div>
    </aside>
@endsection

@push('scripts')
    <script src="@vasset('js/uploader.js')" defer></script>

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
                // Upload form is handled exclusively by uploader.js (corner widget).
                // The old inline pipeline (uploadFiles/uploadSingleFile/setUploadProgress)
                // is intentionally not invoked here — both handlers would otherwise fire
                // the same submit, causing duplicate uploads.

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

                const faTab = event.target.closest('[data-fa-tab]');
                if (faTab) {
                    event.preventDefault();
                    const panel = faTab.closest('[data-file-share]');
                    if (panel) {
                        const key = faTab.dataset.faTab;
                        panel.querySelectorAll('[data-fa-tab]').forEach((b) => {
                            const active = b.dataset.faTab === key;
                            b.classList.toggle('is-active', active);
                            b.setAttribute('aria-selected', active ? 'true' : 'false');
                        });
                        panel.querySelectorAll('[data-fa-tab-panel]').forEach((p) => {
                            p.toggleAttribute('hidden', p.dataset.faTabPanel !== key);
                        });
                    }
                    return;
                }

                const shareSave = event.target.closest('[data-share-save]');
                const shareCopy = event.target.closest('[data-share-copy]');
                const shareRawCopy = event.target.closest('[data-share-raw-copy]');

                if (shareSave || shareCopy || shareRawCopy) {
                    const panel = event.target.closest('[data-file-share]');

                    if (! panel) {
                        return;
                    }

                    event.preventDefault();

                    if (shareCopy) {
                        copyShareLink(panel, '[data-share-link-input]');
                        return;
                    }

                    if (shareRawCopy) {
                        copyShareLink(panel, '[data-share-raw-link-input]');
                        return;
                    }

                    try {
                        setShareBusy(shareSave, true);
                        const data = await sendShareRequest(panel.dataset.shareSettingsUrl, 'PATCH', {
                            share_max_views: panel.querySelector('[data-share-max-views]')?.value || null,
                            share_expires_at: panel.querySelector('[data-share-expires-at]')?.value || null,
                        });
                        updateSharePanel(panel, data.share, data.message);
                    } catch (error) {
                        showShareMessage(panel, error.message || 'Не вдалося зберегти налаштування.', true);
                    } finally {
                        setShareBusy(shareSave, false);
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
                const owningRow = form.closest('[data-file-item]');
                const owningMenu = form.closest('[data-file-share]');

                // Close the action menu immediately — its position:fixed panel
                // can otherwise leak into viewport and cause scrollbars while
                // the parent row pulses.
                if (owningMenu) {
                    owningMenu.removeAttribute('open');
                }

                // Visually mark the file row as leaving so user sees immediate feedback.
                if (owningRow) {
                    owningRow.classList.add('is-leaving');
                    owningRow.setAttribute('aria-busy', 'true');
                }

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

                    const toastMessage = extractStatusFromHtml(html);

                    replaceFilesPageFromHtml(html, response.url || window.location.href, true);

                    if (toastMessage) {
                        showToast(toastMessage);
                    }

                    handleEmptyPageAfterMutation();
                } catch (error) {
                    // Restore the row — delete didn't go through
                    if (owningRow) {
                        owningRow.classList.remove('is-leaving');
                        owningRow.removeAttribute('aria-busy');
                    }
                    showToast(error.message || 'Дію не виконано.', 'error');
                } finally {
                    setShareBusy(submitter, false);
                }
            }

            // Якщо після видалення список порожній і ми не на 1-й сторінці —
            // тихо переходимо на page-1.
            function handleEmptyPageAfterMutation() {
                const region = document.querySelector('[data-files-region]');
                if (! region) return;

                const items = region.querySelector('[data-file-items]');
                if (! items) return;

                // .file-card-item (table) і .file-tile (grid) — обидва мають [data-file-item]
                const hasAny = items.querySelector('[data-file-item]');
                if (hasAny) return;

                const currentUrl = new URL(window.location.href);
                const currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                if (currentPage > 1) {
                    currentUrl.searchParams.set('page', String(currentPage - 1));
                    refreshFilesPage(currentUrl.toString(), true, { region: 'files' });
                }
            }

            function extractStatusFromHtml(html) {
                const doc = new DOMParser().parseFromString(html || '', 'text/html');
                const status = doc.querySelector('[data-flash-area] .status');
                return status ? status.textContent.trim() : '';
            }

            // Toast layer + helper
            function showToast(message, type) {
                if (! message) return;

                let layer = document.querySelector('[data-toast-layer]');
                if (! layer) {
                    layer = document.createElement('div');
                    layer.dataset.toastLayer = '';
                    layer.className = 'fp-toast-layer';
                    // Safety: inline styles in case site.css is cached without toast rules
                    layer.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:80;display:flex;flex-direction:column;gap:10px;pointer-events:none;width:min(380px,calc(100vw - 32px))';
                    document.body.appendChild(layer);
                }

                const toast = document.createElement('div');
                toast.className = 'fp-toast' + (type === 'error' ? ' is-error' : '');
                // Safety baseline (fallback if site.css cache is stale)
                toast.style.cssText = 'pointer-events:auto;display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:12px;align-items:center;padding:12px 14px;background:#fff;color:#0f172a;border:1px solid #e2e8f0;border-left:4px solid '+(type==='error'?'#dc2626':'#16a34a')+';border-radius:12px;box-shadow:0 16px 38px -18px rgba(15,23,42,.45);font-size:14px;line-height:1.4;opacity:0;transform:translateY(12px);transition:opacity .2s,transform .2s';
                toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
                toast.innerHTML = `
                    <span class="fp-toast-icon" aria-hidden="true">
                        ${type === 'error'
                            ? '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
                            : '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'}
                    </span>
                    <span class="fp-toast-message"></span>
                    <button type="button" class="fp-toast-close" aria-label="Закрити">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                `;
                toast.querySelector('.fp-toast-message').textContent = message;

                // Safety: constrain icon/close button if site.css is stale
                const iconEl = toast.querySelector('.fp-toast-icon');
                if (iconEl) {
                    iconEl.style.cssText = 'width:22px;height:22px;flex-shrink:0;display:grid;place-items:center;color:'+(type==='error'?'#dc2626':'#16a34a');
                }
                const closeEl = toast.querySelector('.fp-toast-close');
                if (closeEl) {
                    closeEl.style.cssText = 'background:transparent;border:0;cursor:pointer;width:26px;height:26px;border-radius:6px;display:grid;place-items:center;color:#94a3b8';
                }

                layer.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.classList.add('is-visible');
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0)';
                });

                const dismiss = () => {
                    toast.classList.remove('is-visible');
                    toast.classList.add('is-leaving');
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(6px)';
                    setTimeout(() => toast.remove(), 250);
                };

                toast.querySelector('.fp-toast-close').addEventListener('click', dismiss);
                setTimeout(dismiss, type === 'error' ? 6000 : 3500);
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

                    restoreLastStorageChoice();

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

            function restoreLastStorageChoice() {
                try {
                    const savedId = window.localStorage?.getItem('fp_last_storage_group_id');
                    if (! savedId) return;

                    const storageDropdown = document.querySelector('.upload-control-storage[data-upload-dropdown]');
                    if (! storageDropdown) return;

                    // Don't override an explicit "old value" from server (e.g. validation re-render)
                    const oldValue = storageDropdown.querySelector('[data-upload-dropdown-input]')?.value || '';
                    if (oldValue && oldValue !== '') return;

                    const option = storageDropdown.querySelector(`[data-upload-dropdown-option][data-value="${CSS.escape(savedId)}"]`);
                    if (option) {
                        selectUploadDropdownOption(option);
                    }
                } catch (_) {
                    // localStorage may throw in private mode / sandboxed contexts
                }
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

                // Capture current file IDs so newly-arrived rows can fade-in
                const previousFileIds = new Set();
                document.querySelectorAll('[data-file-item][data-file-id]').forEach((row) => {
                    previousFileIds.add(row.dataset.fileId);
                });

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

                    // Sync per-folder file count badges from the new HTML.
                    // After upload or navigation, the counts in the sidebar must reflect reality.
                    syncFolderCounts(doc);

                    // Sync the upload form's folder_id so subsequent uploads land in the currently
                    // viewed folder. The hidden input + visible dropdown value + selected option all update.
                    syncUploadFolderSelection(doc);
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

                // Mark new rows so CSS can fade them in
                document.querySelectorAll('[data-file-item][data-file-id]').forEach((row) => {
                    if (! previousFileIds.has(row.dataset.fileId)) {
                        row.classList.add('fp-row-just-added');
                        setTimeout(() => row.classList.remove('fp-row-just-added'), 1500);
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

            function syncFolderCounts(doc) {
                // Map href → count from the freshly fetched doc, then apply to current sidebar.
                const fresh = new Map();
                doc.querySelectorAll('.folder-item').forEach((item) => {
                    const href = item.getAttribute('href');
                    const countEl = item.querySelector('.folder-item-count');
                    if (href && countEl) {
                        fresh.set(href, countEl.textContent.trim());
                    }
                });

                document.querySelectorAll('.folder-item').forEach((item) => {
                    const href = item.getAttribute('href');
                    const countEl = item.querySelector('.folder-item-count');
                    if (href && countEl && fresh.has(href)) {
                        const next = fresh.get(href);
                        if (countEl.textContent.trim() !== next) {
                            countEl.textContent = next;
                            countEl.classList.add('folder-item-count-bump');
                            setTimeout(() => countEl.classList.remove('folder-item-count-bump'), 700);
                        }
                    }
                });
            }

            function syncUploadFolderSelection(doc) {
                const nextInput = doc.querySelector('[data-upload-dropdown-input][name="folder_id"]');
                const nextValueEl = doc.querySelector('.upload-control-folder [data-upload-dropdown-value]');
                if (! nextInput) return;

                const currentInput = document.querySelector('[data-upload-dropdown-input][name="folder_id"]');
                const currentValueEl = document.querySelector('.upload-control-folder [data-upload-dropdown-value]');
                const currentDropdown = document.querySelector('.upload-control-folder [data-upload-dropdown-menu]');
                if (! currentInput) return;

                const newValue = nextInput.value || '';
                if (currentInput.value !== newValue) {
                    currentInput.value = newValue;
                    currentInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (currentValueEl && nextValueEl) {
                    currentValueEl.textContent = nextValueEl.textContent;
                }

                if (currentDropdown) {
                    currentDropdown.querySelectorAll('[data-upload-dropdown-option]').forEach((opt) => {
                        const isSelected = (opt.dataset.value || '') === newValue;
                        opt.classList.toggle('is-selected', isSelected);
                        opt.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    });
                }
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

            async function saveTagsForFile(panel) {
                if (! panel) return;
                const url = panel.dataset.tagsUrl;
                const input = panel.querySelector('[data-action-tags-input]');
                const msg = panel.querySelector('[data-action-tags-message]');
                if (! url || ! input) return;

                const button = panel.querySelector('[data-action-tags-save]');
                if (button) button.disabled = true;
                if (msg) { msg.textContent = ''; msg.classList.remove('is-error'); }

                try {
                    const data = await sendShareRequest(url, 'PATCH', { tags: input.value });
                    if (msg) msg.textContent = data.message || 'Збережено.';
                    // Soft-refresh file list so chips update inline (and sidebar count)
                    try { refreshFilesPage(window.location.href, false, { region: 'files' }); } catch (_) {}
                } catch (e) {
                    if (msg) { msg.textContent = e.message || 'Не вдалося зберегти.'; msg.classList.add('is-error'); }
                } finally {
                    if (button) button.disabled = false;
                }
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
                const toggle = panel.querySelector('[data-share-toggle]');
                const status = panel.querySelector('[data-share-status]');
                const linkInput = panel.querySelector('[data-share-link-input]');
                const openLink = panel.querySelector('[data-share-open]');
                const maxViews = panel.querySelector('[data-share-max-views]');
                const expiresAt = panel.querySelector('[data-share-expires-at]');
                const usage = panel.querySelector('[data-share-usage]');

                // Legacy (.share-settings) + new (.fa-share-section) markup
                panel.querySelector('.share-settings')?.classList.toggle('is-enabled', enabled);
                panel.querySelector('.fa-share-section')?.classList.toggle('is-enabled', enabled);

                if (enabledBlock) enabledBlock.hidden = ! enabled;
                if (disabledBlock) disabledBlock.hidden = enabled;

                if (toggle) {
                    toggle.checked = enabled;
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

                const rawLinkInput = panel.querySelector('[data-share-raw-link-input]');
                const rawOpenLink  = panel.querySelector('[data-share-raw-open]');
                if (rawLinkInput) {
                    rawLinkInput.value = share?.raw_url || '';
                }
                if (rawOpenLink) {
                    rawOpenLink.href = share?.raw_url || '#';
                    rawOpenLink.toggleAttribute('aria-disabled', ! share?.raw_url);
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

            function copyShareLink(panel, selector = '[data-share-link-input]') {
                const input = panel.querySelector(selector);

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

            /* ====================================================
               Listen for uploader.js "all done" → soft refresh file list
               (no full page reload — preserves scroll, action menus, etc.)
               ==================================================== */
            window.addEventListener('fp-uploader:refresh-needed', () => {
                try {
                    refreshFilesPage(window.location.href, false, { region: 'files' });
                } catch (e) {
                    console.warn('fp-uploader refresh failed:', e);
                }
            });

            /* ====================================================
               Compact action panel: tag chip-input with autosave,
               share toggle, and tab switching.
               ==================================================== */
            initActionPanelTagChips();
            initShareToggle();

            function initActionPanelTagChips() {
                document.addEventListener('focusin', (e) => {
                    const container = e.target.closest('[data-file-tags-container]');
                    if (container) setupActionPanelTagChips(container);
                });
                // Eager setup for already-open panels (e.g. after soft-refresh)
                document.querySelectorAll('[data-file-tags-container]').forEach(setupActionPanelTagChips);
            }

            function setupActionPanelTagChips(container) {
                if (container.dataset.tagsInit === '1') return;
                container.dataset.tagsInit = '1';

                const chipsArea = container.querySelector('[data-action-tags-chips]');
                const typing    = container.querySelector('[data-action-tags-typing]');
                const hidden    = container.querySelector('[data-action-tags-input]');
                const status    = container.querySelector('[data-action-tags-status]');
                const message   = container.querySelector('[data-action-tags-message]');
                const panel     = container.closest('[data-file-share]');
                const url       = panel?.dataset.tagsUrl;
                if (! chipsArea || ! typing || ! hidden || ! url) return;

                const state = new Set();
                let saveTimer = null;

                const norm = (raw) => String(raw || '').trim().toLowerCase().replace(/\s+/g, ' ').slice(0, 64);

                function setStatus(text, kind) {
                    if (! status) return;
                    status.textContent = text || '';
                    status.className = 'fa-section-state' + (kind ? ' is-' + kind : '');
                }

                function syncHidden() {
                    hidden.value = Array.from(state).join(', ');
                }

                async function persist() {
                    setStatus('зберігаю…', 'saving');
                    try {
                        const data = await sendShareRequest(url, 'PATCH', { tags: hidden.value });
                        setStatus('✓ збережено', 'success');
                        setTimeout(() => setStatus('', null), 1800);
                        if (message) message.textContent = '';
                    } catch (e) {
                        setStatus('✗ помилка', 'error');
                        if (message) {
                            message.textContent = e.message || 'Не вдалося зберегти.';
                            message.classList.add('is-error');
                        }
                    }
                }

                function scheduleSave() {
                    clearTimeout(saveTimer);
                    saveTimer = setTimeout(persist, 500);
                }

                function cssEscape(s) {
                    return (window.CSS && CSS.escape) ? CSS.escape(s) : s.replace(/["\\]/g, '\\$&');
                }

                function renderChip(name) {
                    const chip = document.createElement('span');
                    chip.className = 'upload-tag-chip';
                    chip.dataset.chipName = name;
                    chip.innerHTML = '<span class="upload-tag-chip-text"></span><button type="button" class="upload-tag-chip-remove" aria-label="Видалити тег">✕</button>';
                    chip.querySelector('.upload-tag-chip-text').textContent = name;
                    chip.querySelector('.upload-tag-chip-remove').addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (state.delete(name)) {
                            chip.remove();
                            syncHidden();
                            scheduleSave();
                            typing.focus();
                        }
                    });
                    chipsArea.insertBefore(chip, typing);
                }

                function addTag(raw) {
                    const name = norm(raw);
                    if (! name || state.has(name)) return;
                    state.add(name);
                    renderChip(name);
                    syncHidden();
                    scheduleSave();
                }

                typing.addEventListener('input', () => {
                    const v = typing.value;
                    if (/[,;\n]/.test(v)) {
                        const parts = v.split(/[,;\n]/);
                        const tail  = parts.pop();
                        parts.forEach(addTag);
                        typing.value = tail || '';
                    }
                });

                typing.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === 'Tab') {
                        if (typing.value.trim() !== '') {
                            e.preventDefault();
                            addTag(typing.value);
                            typing.value = '';
                        }
                    } else if (e.key === 'Backspace' && typing.value === '' && state.size > 0) {
                        const last = Array.from(state).pop();
                        if (state.delete(last)) {
                            const c = chipsArea.querySelector('[data-chip-name="' + cssEscape(last) + '"]');
                            if (c) c.remove();
                            syncHidden();
                            scheduleSave();
                        }
                    }
                });

                typing.addEventListener('blur', () => {
                    if (typing.value.trim() !== '') {
                        addTag(typing.value);
                        typing.value = '';
                    }
                });

                container.addEventListener('click', (e) => {
                    if (e.target === typing) return;
                    if (e.target.closest('.upload-tag-chip-remove')) return;
                    typing.focus();
                });

                // Seed chips from initial CSV (file already has tags)
                const initial = (hidden.value || '').split(/[,;]/).map(norm).filter(Boolean);
                initial.forEach((name) => {
                    if (! state.has(name)) {
                        state.add(name);
                        renderChip(name);
                    }
                });
                syncHidden();
            }

            function initShareToggle() {
                document.addEventListener('change', async (e) => {
                    const toggle = e.target.closest('[data-share-toggle]');
                    if (! toggle) return;

                    const panel = toggle.closest('[data-file-share]');
                    if (! panel) return;

                    toggle.disabled = true;

                    try {
                        const url = toggle.checked ? panel.dataset.shareUrl : panel.dataset.shareDisableUrl;
                        const method = toggle.checked ? 'POST' : 'DELETE';
                        const data = await sendShareRequest(url, method);
                        updateSharePanel(panel, data.share, data.message);
                    } catch (error) {
                        // Revert checkbox on failure
                        toggle.checked = ! toggle.checked;
                        showShareMessage(panel, error.message || 'Не вдалося змінити стан публічного лінка.', true);
                    } finally {
                        toggle.disabled = false;
                    }
                });
            }

            /* ====================================================
               Upload form tag chip input: convert typed text + comma/Enter into a removable chip.
               Hidden [name="tags"] stays in sync as a CSV for the form submit.
               ==================================================== */
            initTagChipInput();

            function initTagChipInput() {
                document.querySelectorAll('[data-upload-tags-container]').forEach(setupOne);

                function setupOne(container) {
                    if (container.dataset.tagsInit === '1') return;
                    container.dataset.tagsInit = '1';

                    const chipsArea = container.querySelector('[data-upload-tags-chips]');
                    const typing    = container.querySelector('[data-upload-tags-typing]');
                    const hidden    = container.querySelector('[data-upload-tags]');
                    if (! chipsArea || ! typing || ! hidden) return;

                    const state = new Set();

                    function syncHidden() {
                        hidden.value = Array.from(state).join(', ');
                    }

                    function normalize(raw) {
                        return String(raw || '')
                            .trim()
                            .toLowerCase()
                            .replace(/\s+/g, ' ')
                            .slice(0, 64);
                    }

                    function addTag(raw) {
                        const name = normalize(raw);
                        if (! name || state.has(name)) return;
                        state.add(name);
                        renderChip(name);
                        syncHidden();
                    }

                    function removeTag(name) {
                        if (! state.delete(name)) return;
                        const chip = chipsArea.querySelector('[data-chip-name="' + cssEscape(name) + '"]');
                        if (chip) chip.remove();
                        syncHidden();
                    }

                    function cssEscape(s) {
                        return (window.CSS && CSS.escape) ? CSS.escape(s) : s.replace(/["\\]/g, '\\$&');
                    }

                    function renderChip(name) {
                        const chip = document.createElement('span');
                        chip.className = 'upload-tag-chip';
                        chip.dataset.chipName = name;
                        chip.innerHTML = '<span class="upload-tag-chip-text"></span><button type="button" class="upload-tag-chip-remove" aria-label="Видалити тег">✕</button>';
                        chip.querySelector('.upload-tag-chip-text').textContent = name;
                        chip.querySelector('.upload-tag-chip-remove').addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            removeTag(name);
                            typing.focus();
                        });
                        chipsArea.insertBefore(chip, typing);
                    }

                    function flushBuffer() {
                        const v = typing.value;
                        if (! v) return;
                        const parts = v.split(/[,;\n]/);
                        const tail  = parts.pop();
                        parts.forEach((p) => addTag(p));
                        typing.value = tail || '';
                    }

                    typing.addEventListener('input', flushBuffer);

                    typing.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === 'Tab') {
                            if (typing.value.trim() !== '') {
                                e.preventDefault();
                                addTag(typing.value);
                                typing.value = '';
                            }
                        } else if (e.key === 'Backspace' && typing.value === '' && state.size > 0) {
                            const last = Array.from(state).pop();
                            removeTag(last);
                        }
                    });

                    typing.addEventListener('blur', () => {
                        if (typing.value.trim() !== '') {
                            addTag(typing.value);
                            typing.value = '';
                        }
                    });

                    // Click anywhere in the container focuses the typing input
                    container.addEventListener('click', (e) => {
                        if (e.target === typing) return;
                        if (e.target.closest('.upload-tag-chip-remove')) return;
                        typing.focus();
                    });

                    // Form submit fallback: ensure unflushed text is captured
                    const form = container.closest('form');
                    if (form) {
                        form.addEventListener('submit', () => {
                            if (typing.value.trim() !== '') {
                                addTag(typing.value);
                                typing.value = '';
                            }
                            syncHidden();
                        }, true); // capture, runs before uploader.js submit handler
                    }

                    // Seed initial value (e.g. when form is re-rendered with old input)
                    const initial = hidden.value || '';
                    if (initial) {
                        initial.split(/[,;]/).forEach((p) => addTag(p));
                    }
                }
            }

            /* ====================================================
               Image preview fallback: when a tile thumbnail fails to load
               (e.g. Telegram throttled the bot mid-batch), retry once after
               a short jittered delay, then fall back to a clean placeholder.
               ==================================================== */
            initPreviewFallback();

            function initPreviewFallback() {
                document.addEventListener('error', (event) => {
                    const img = event.target;
                    if (!img || img.tagName !== 'IMG' || !img.matches?.('[data-preview-img]')) return;

                    const attempts = parseInt(img.dataset.previewAttempts || '0', 10);

                    if (attempts < 1) {
                        // First failure: retry once after 800–1500ms (jitter to spread retries)
                        img.dataset.previewAttempts = String(attempts + 1);
                        const wait = 800 + Math.random() * 700;
                        const original = img.src.split('?')[0];
                        setTimeout(() => {
                            img.src = original + '?retry=' + Date.now();
                        }, wait);
                        return;
                    }

                    // Give up: swap <img> for a clean type-label placeholder
                    const wrap = img.closest('.file-tile-preview');
                    if (!wrap) return;
                    const label = img.dataset.typeLabel || 'FILE';
                    wrap.classList.add('file-tile-preview-empty');
                    wrap.innerHTML = `<span>${label}</span>`;
                }, true); // capture phase — error events on <img> don't bubble
            }

            /* ====================================================
               Bulk selection: multi-select + bulk delete/move
               ==================================================== */
            initBulkSelection();

            function initBulkSelection() {
                const selected = new Set();
                const bar = document.querySelector('[data-fp-bulk-bar]');
                if (! bar) return;

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const countEl    = bar.querySelector('[data-fp-bulk-count]');
                const deleteBtn  = bar.querySelector('[data-fp-bulk-delete]');
                const clearBtn   = bar.querySelector('[data-fp-bulk-clear]');
                const moveOptions = bar.querySelectorAll('[data-fp-bulk-move-folder]');
                const moveDetails = bar.querySelector('[data-fp-bulk-move]');

                const refreshBar = () => {
                    const n = selected.size;
                    if (countEl) countEl.textContent = String(n);
                    bar.toggleAttribute('hidden', n === 0);
                    // sync selected class on rows
                    document.querySelectorAll('[data-file-item][data-file-id]').forEach((row) => {
                        const id = row.dataset.fileId;
                        const isOn = selected.has(id);
                        row.classList.toggle('is-selected', isOn);
                        const cb = row.querySelector('[data-fp-select]');
                        if (cb) cb.checked = isOn;
                    });
                    refreshSelectAllCheckbox();
                };

                const refreshSelectAllCheckbox = () => {
                    const masters = document.querySelectorAll('[data-fp-select-all]');
                    const rowIds = Array.from(document.querySelectorAll('[data-file-item][data-file-id]'))
                        .map((r) => r.dataset.fileId);
                    if (rowIds.length === 0) {
                        masters.forEach((m) => {
                            m.checked = false;
                            m.parentElement?.classList.remove('is-indeterminate');
                        });
                        return;
                    }
                    const allSelected = rowIds.every((id) => selected.has(id));
                    const someSelected = rowIds.some((id) => selected.has(id));
                    masters.forEach((m) => {
                        m.checked = allSelected;
                        m.parentElement?.classList.toggle('is-indeterminate', someSelected && ! allSelected);
                    });
                };

                // Re-sync after AJAX swaps the file region
                const observer = new MutationObserver(() => {
                    // Items might have been replaced; prune selected IDs that no longer exist
                    const currentIds = new Set(
                        Array.from(document.querySelectorAll('[data-file-item][data-file-id]'))
                            .map((r) => r.dataset.fileId)
                    );
                    for (const id of Array.from(selected)) {
                        if (! currentIds.has(id)) selected.delete(id);
                    }
                    refreshBar();
                });
                const filesRegion = document.querySelector('[data-files-region]');
                if (filesRegion) {
                    observer.observe(filesRegion, { childList: true, subtree: true });
                }

                // Per-row checkbox click
                document.addEventListener('change', (event) => {
                    const cb = event.target.closest('[data-fp-select]');
                    if (cb) {
                        const row = cb.closest('[data-file-item][data-file-id]');
                        const id = row?.dataset.fileId;
                        if (! id) return;
                        if (cb.checked) selected.add(id); else selected.delete(id);
                        refreshBar();
                        return;
                    }
                    const master = event.target.closest('[data-fp-select-all]');
                    if (master) {
                        const ids = Array.from(document.querySelectorAll('[data-file-item][data-file-id]'))
                            .map((r) => r.dataset.fileId);
                        if (master.checked) ids.forEach((id) => selected.add(id));
                        else ids.forEach((id) => selected.delete(id));
                        refreshBar();
                    }
                });

                // Clear button
                clearBtn?.addEventListener('click', () => {
                    selected.clear();
                    refreshBar();
                });

                // Bulk delete
                deleteBtn?.addEventListener('click', async () => {
                    const n = selected.size;
                    if (n === 0) return;

                    if (! confirm(`Видалити вибраних файлів: ${n}? Дію не можна скасувати.`)) {
                        return;
                    }

                    // Mark all selected rows as leaving for visual feedback
                    document.querySelectorAll('[data-file-item][data-file-id].is-selected').forEach((row) => {
                        row.classList.add('is-leaving');
                        row.setAttribute('aria-busy', 'true');
                    });

                    deleteBtn.setAttribute('disabled', 'true');
                    deleteBtn.setAttribute('aria-busy', 'true');

                    try {
                        const ids = Array.from(selected);
                        const fd = new FormData();
                        ids.forEach((id) => fd.append('ids[]', id));

                        const response = await fetch(@json(route('files.bulk-delete')), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: fd,
                            credentials: 'same-origin',
                        });

                        if (! response.ok) {
                            const text = await response.text();
                            throw new Error(extractErrorFromHtml(text) || `HTTP ${response.status}`);
                        }

                        const data = await response.json().catch(() => ({}));
                        showToast(data.message || `Видалено: ${n}`);

                        selected.clear();
                        refreshBar();

                        // Refresh the file list region
                        refreshFilesPage(window.location.href, false, { region: 'files' });
                    } catch (error) {
                        // Restore rows on failure
                        document.querySelectorAll('[data-file-item].is-leaving').forEach((row) => {
                            row.classList.remove('is-leaving');
                            row.removeAttribute('aria-busy');
                        });
                        showToast(error.message || 'Не вдалося видалити файли.', 'error');
                    } finally {
                        deleteBtn.removeAttribute('disabled');
                        deleteBtn.removeAttribute('aria-busy');
                    }
                });

                // Bulk move
                moveOptions.forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const n = selected.size;
                        if (n === 0) return;

                        const targetFolderId = btn.dataset.fpBulkMoveFolder; // "" for root
                        const folderLabel = btn.textContent.trim();

                        if (! confirm(`Перемістити ${n} файл(и/ів) у «${folderLabel}»?`)) {
                            return;
                        }

                        // Close the details menu
                        if (moveDetails) moveDetails.removeAttribute('open');

                        // Mark selected rows as leaving (they'll be re-rendered)
                        document.querySelectorAll('[data-file-item][data-file-id].is-selected').forEach((row) => {
                            row.classList.add('is-leaving');
                            row.setAttribute('aria-busy', 'true');
                        });

                        try {
                            const ids = Array.from(selected);
                            const fd = new FormData();
                            ids.forEach((id) => fd.append('ids[]', id));
                            if (targetFolderId !== '') fd.append('folder_id', targetFolderId);

                            const response = await fetch(@json(route('files.bulk-move')), {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrf,
                                },
                                body: fd,
                                credentials: 'same-origin',
                            });

                            if (! response.ok) {
                                const text = await response.text();
                                throw new Error(extractErrorFromHtml(text) || `HTTP ${response.status}`);
                            }

                            const data = await response.json().catch(() => ({}));
                            showToast(data.message || `Переміщено: ${n}`);

                            selected.clear();
                            refreshBar();

                            refreshFilesPage(window.location.href, false, { region: 'files' });
                        } catch (error) {
                            document.querySelectorAll('[data-file-item].is-leaving').forEach((row) => {
                                row.classList.remove('is-leaving');
                                row.removeAttribute('aria-busy');
                            });
                            showToast(error.message || 'Не вдалося перемістити файли.', 'error');
                        }
                    });
                });

                // Initial sync
                refreshBar();
            }
        })();
    </script>
@endpush
