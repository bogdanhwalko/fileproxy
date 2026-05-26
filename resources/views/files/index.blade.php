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

    <div class="upload-row">
        <details class="upload-shell" data-upload-shell>
            <summary class="upload-shell-trigger">
            <span class="upload-shell-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
            </span>
            <div class="upload-shell-text">
                <strong>Завантажити файли</strong>
                <span>або перетягніть сюди · до {{ $telegramUploadMaxMb }} MB на файл</span>
            </div>
            <span class="upload-shell-chevron" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </span>
        </summary>
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

            <div class="upload-extras">
                <div class="upload-tags upload-tags-inline" data-upload-tags-container>
                    <span class="upload-tags-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                            <line x1="7" y1="7" x2="7.01" y2="7"/>
                        </svg>
                    </span>
                    <div class="upload-tags-field" data-upload-tags-chips>
                        <input
                            type="text"
                            class="upload-tags-typing"
                            data-upload-tags-typing
                            maxlength="64"
                            placeholder="додати тег + Enter"
                            autocomplete="off"
                        >
                    </div>
                    <button type="button" class="upload-tags-hint-toggle" data-upload-tags-hint
                        title="Розділяй комою або Enter. Для незахищених файлів теги додаються як #hashtag у Telegram caption.">i</button>
                    <input type="hidden" name="tags" value="" data-upload-tags>
                </div>

                <label class="upload-protect-switch" data-upload-protect
                    title="Розбити на зашифровані частини, розкидати по групах. Максимум {{ $protectedUploadMaxMb }} МБ. Працює тільки з вибраною своєю Telegram-групою.">
                    <input type="checkbox" name="is_protected" value="1" data-upload-protect-checkbox class="upload-protect-checkbox-input">
                    <span class="upload-protect-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <span class="upload-protect-label">Захистити</span>
                    <span class="fa-switch-track"><span class="fa-switch-knob"></span></span>
                </label>
            </div>

            <p class="upload-protect-hint" data-upload-protect-hint hidden>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Файл буде розбито на зашифровані частини і розкидано по групах. Максимум <strong>{{ $protectedUploadMaxMb }} МБ</strong>. Дешифрується тільки на цьому сервері.
            </p>

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
        </details>

        <div class="upload-create-stack">
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
    </div>

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

                    <details class="folders-form-password" data-folders-form-password>
                        <summary>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <span>Захистити папку паролем</span>
                        </summary>
                        <div class="folders-form-password-body">
                            <input class="field" type="password" name="password" minlength="4" maxlength="128" placeholder="Пароль (мінімум 4 символи)" autocomplete="new-password">
                            <p class="folders-form-password-hint">Файли в захищеній папці автоматично шифруються AES-GCM перед відправкою у Telegram. Пароль не можна додати пізніше — лише прибрати або змінити.</p>
                        </div>
                    </details>

                    <div class="folders-form-actions">
                        <button class="button secondary" type="button" data-folders-form-cancel>Скасувати</button>
                        <button class="button" type="submit">Створити</button>
                    </div>
                </form>

                <div data-sidebar-section="folders">
                @if ($folders->count() > 8)
                    <div class="sidebar-controls">
                        <div class="sidebar-search-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <input type="search" placeholder="Пошук папок…" data-sidebar-search aria-label="Пошук папок">
                        </div>
                        <div class="sidebar-sort" role="group" aria-label="Сортування">
                            <button type="button" class="is-active" data-sidebar-sort="usage" title="За кількістю файлів">↓ файли</button>
                            <button type="button" data-sidebar-sort="alpha" title="За алфавітом">A–Я</button>
                        </div>
                    </div>
                @endif

                <nav class="folders-list-v2 sidebar-list" aria-label="Список папок" data-sidebar-list>
                    <a class="folder-item folder-item-pinned {{ $folderFilter === 'all' ? 'is-active' : '' }}" href="{{ route('files.index') }}" data-sidebar-pin>
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

                    <a class="folder-item folder-item-pinned {{ $folderFilter === 'root' ? 'is-active' : '' }}" href="{{ route('files.index', ['folder' => 'root']) }}" data-sidebar-pin>
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
                        <div class="folders-divider" aria-hidden="true" data-sidebar-pin></div>
                    @endif

                    @foreach ($folders as $folder)
                        @php
                            $folderColor = $folder->color && isset(\App\Models\FileFolder::COLOR_PALETTE[$folder->color]) ? \App\Models\FileFolder::COLOR_PALETTE[$folder->color] : null;
                            $folderLocked = $folder->is_password_protected && ! in_array($folder->id, $unlockedFolderIds ?? [], true);
                            $folderUnlocked = $folder->is_password_protected && in_array($folder->id, $unlockedFolderIds ?? [], true);
                        @endphp
                        <div class="folder-row-v2 {{ $folderColor ? 'folder-row-colored' : '' }} {{ $folderLocked ? 'folder-row-locked' : '' }} {{ $folderUnlocked ? 'folder-row-unlocked' : '' }}"
                            @if ($folderColor) style="--folder-color:{{ $folderColor }}" @endif
                            data-sidebar-item
                            data-name="{{ mb_strtolower($folder->name) }}"
                            data-count="{{ $folder->files_count }}">
                            <a class="folder-item {{ $activeFolder?->id === $folder->id ? 'is-active' : '' }}"
                                href="{{ $folderLocked ? route('folders.unlock-prompt', $folder) : route('files.index', ['folder' => $folder->id]) }}"
                                @if ($folderLocked) data-no-ajax @endif>
                                <span class="folder-item-icon" aria-hidden="true">
                                    @if ($folder->is_password_protected)
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            @if ($folderUnlocked)
                                                <rect x="3" y="11" width="18" height="11" rx="2"/>
                                                <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
                                            @else
                                                <rect x="3" y="11" width="18" height="11" rx="2"/>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                            @endif
                                        </svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                        </svg>
                                    @endif
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

                @if ($folders->count() > 8)
                    <button type="button" class="sidebar-show-all" data-sidebar-show-all data-collapsed-count="8" hidden>
                        Показати всі ({{ $folders->count() - 8 }} ще) ▾
                    </button>
                @endif
                </div>{{-- /data-sidebar-section=folders --}}

                @if ($tags->isNotEmpty())
                    <header class="folders-header-v2 folders-header-tags">
                        <div class="folders-header-text">
                            <strong>Теги</strong>
                            <span class="folders-header-count">{{ $tags->count() }}</span>
                        </div>
                    </header>

                    <div data-sidebar-section="tags">
                        @if ($activeTag)
                            <a class="tag-chip tag-chip-clear" href="{{ route('files.index', array_filter(['folder' => $folderFilter !== 'all' ? $folderFilter : null, 'view' => $display])) }}" data-sidebar-pin>
                                ✕ Скинути тег
                            </a>
                        @endif

                        @if ($tags->count() > 8)
                            <div class="sidebar-controls">
                                <div class="sidebar-search-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="11" cy="11" r="7"/>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                    <input type="search" placeholder="Пошук тегів…" data-sidebar-search aria-label="Пошук тегів">
                                </div>
                                <div class="sidebar-sort" role="group" aria-label="Сортування">
                                    <button type="button" class="is-active" data-sidebar-sort="usage" title="За частотою">↓ файли</button>
                                    <button type="button" data-sidebar-sort="alpha" title="За алфавітом">A–Я</button>
                                </div>
                            </div>
                        @endif

                        <nav class="tag-list sidebar-list" aria-label="Список тегів" data-sidebar-list>
                            @foreach ($tags as $tag)
                                <a
                                    class="tag-chip {{ $activeTag?->id === $tag->id ? 'is-active' : '' }}"
                                    href="{{ route('files.index', array_filter(['folder' => $folderFilter !== 'all' ? $folderFilter : null, 'view' => $display, 'tag' => $tag->name])) }}"
                                    data-sidebar-item
                                    data-name="{{ mb_strtolower($tag->name) }}"
                                    data-count="{{ $tag->files_count }}"
                                >
                                    #{{ $tag->name }}
                                    <span class="tag-chip-count">{{ $tag->files_count }}</span>
                                </a>
                            @endforeach
                        </nav>

                        @if ($tags->count() > 8)
                            <button type="button" class="sidebar-show-all" data-sidebar-show-all data-collapsed-count="8" hidden>
                                Показати всі (+{{ $tags->count() - 8 }} ще) ▾
                            </button>
                        @endif
                    </div>
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

                    @if ($display === 'grid')
                        <div class="density-toggle" role="group" aria-label="Щільність відображення" data-density-toggle>
                            <button type="button" class="density-btn" data-density="comfortable" title="Комфортно — великі плитки">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="3" width="8" height="8" rx="1.5"/>
                                    <rect x="13" y="3" width="8" height="8" rx="1.5"/>
                                    <rect x="3" y="13" width="8" height="8" rx="1.5"/>
                                    <rect x="13" y="13" width="8" height="8" rx="1.5"/>
                                </svg>
                            </button>
                            <button type="button" class="density-btn" data-density="compact" title="Компактно — менші плитки">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="3" width="5" height="5" rx="1"/>
                                    <rect x="10" y="3" width="5" height="5" rx="1"/>
                                    <rect x="17" y="3" width="4" height="5" rx="1"/>
                                    <rect x="3" y="10" width="5" height="5" rx="1"/>
                                    <rect x="10" y="10" width="5" height="5" rx="1"/>
                                    <rect x="17" y="10" width="4" height="5" rx="1"/>
                                    <rect x="3" y="17" width="5" height="4" rx="1"/>
                                    <rect x="10" y="17" width="5" height="4" rx="1"/>
                                    <rect x="17" y="17" width="4" height="4" rx="1"/>
                                </svg>
                            </button>
                            <button type="button" class="density-btn" data-density="list" title="Списком — один рядок на файл">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="3" y1="6" x2="21" y2="6"/>
                                    <line x1="3" y1="12" x2="21" y2="12"/>
                                    <line x1="3" y1="18" x2="21" y2="18"/>
                                </svg>
                            </button>
                        </div>

                        <a
                            class="preview-toggle {{ $imagePreviews ? 'is-on' : 'is-off' }}"
                            href="{{ route('files.index', array_merge(request()->except(['page', 'image_previews']), ['image_previews' => $imagePreviews ? 0 : 1])) }}"
                            title="{{ $imagePreviews ? 'Вимкнути передперегляд фото' : 'Увімкнути передперегляд фото' }}"
                            aria-label="{{ $imagePreviews ? 'Вимкнути передперегляд фото' : 'Увімкнути передперегляд фото' }}"
                        >
                            @if ($imagePreviews)
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                    <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            @endif
                        </a>
                    @endif
                </div>
            </div>

            @if ($display === 'grid')
                <div class="file-grid {{ $imagePreviews ? 'with-previews' : '' }}" data-file-items>
                    @forelse ($files as $file)
                        <article class="file-tile file-tile-status-{{ $file->status }}" data-file-item data-file-id="{{ $file->id }}"
                            @if (! $imagePreviews && $file->is_uploaded && $file->is_image && ! $file->is_protected)
                                data-quickpreview-src="{{ route('files.inline', $file) }}"
                                data-quickpreview-name="{{ $file->original_name }}"
                                data-quickpreview-gradient="{{ $file->placeholder_gradient }}"
                            @endif
                        >
                            <label class="fp-select-checkbox fp-select-checkbox-tile" title="Вибрати файл">
                                <input type="checkbox" data-fp-select aria-label="Вибрати {{ $file->original_name }}">
                                <span class="fp-select-mark" aria-hidden="true">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                            </label>
                            @if ($imagePreviews && $file->is_uploaded && $file->is_image && ! $file->is_protected)
                                <a class="file-tile-preview" href="{{ route('files.preview', $file) }}" aria-label="Відкрити {{ $file->original_name }}" style="background-image: {{ $file->placeholder_gradient }};"
                                    data-lightbox
                                    data-lightbox-src="{{ route('files.inline', $file) }}"
                                    data-lightbox-name="{{ $file->original_name }}"
                                    data-lightbox-meta="{{ $file->human_size }} · {{ $file->mime_type ?? 'image' }}"
                                    data-lightbox-download="{{ route('files.download', $file) }}"
                                    data-lightbox-href="{{ route('files.preview', $file) }}"
                                >
                                    <img
                                        src="{{ route('files.inline', $file) }}"
                                        alt="{{ $file->original_name }}"
                                        loading="lazy"
                                        decoding="async"
                                        class="blur-up-img"
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
                                <tr class="file-row-status-{{ $file->status }}" data-file-item data-file-id="{{ $file->id }}"
                                    @if ($file->is_uploaded && $file->is_image && ! $file->is_protected)
                                        data-quickpreview-src="{{ route('files.inline', $file) }}"
                                        data-quickpreview-name="{{ $file->original_name }}"
                                        data-quickpreview-gradient="{{ $file->placeholder_gradient }}"
                                    @endif
                                >
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
    <div class="fp-bulk-bar"
        data-fp-bulk-bar
        data-bulk-delete-url="{{ route('files.bulk-delete') }}"
        data-bulk-move-url="{{ route('files.bulk-move') }}"
        hidden
        role="region"
        aria-label="Дії над вибраними файлами"
    >
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

    <script src="@vasset('js/files-page.js')" defer></script>
@endpush
