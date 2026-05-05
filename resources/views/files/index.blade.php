@extends('layouts.site')

@section('title', 'Файли - FileProxy')

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
                <a class="button secondary nav-button" href="{{ route('admin.users.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5z"/>
                    </svg>
                    Адмінка
                </a>
            @endif
            <a class="button secondary nav-button" href="{{ route('telegram-settings.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 2 11 13"/>
                    <path d="M22 2 15 22l-4-9-9-4z"/>
                </svg>
                Telegram-сховище
            </a>
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button class="button secondary nav-button nav-button-logout" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Вийти
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

    <section class="stats stats-v2" aria-label="Статистика сховища">
        <div class="stat stat-primary">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['total'] }}</strong>
                <span>Усього файлів</span>
            </div>
        </div>
        <div class="stat stat-accent">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M3 5v6c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                    <path d="M3 11v6c0 1.66 4 3 9 3s9-1.34 9-3v-6"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['storage'] }}</strong>
                <span>Зайнято місця</span>
            </div>
        </div>
        <div class="stat stat-violet">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['folders'] }}</strong>
                <span>Папки</span>
            </div>
        </div>
        <div class="stat stat-telegram">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 2 11 13"/>
                    <path d="M22 2 15 22l-4-9-9-4z"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['telegram'] }}</strong>
                <span>У Telegram</span>
            </div>
        </div>
        <div class="stat stat-ink">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['current'] }}</strong>
                <span>У поточному розділі</span>
            </div>
        </div>
    </section>

    <section class="panel upload-panel upload-panel-v2">
        <header class="upload-hero">
            <div class="upload-hero-text">
                <span class="section-kicker">Нове завантаження</span>
                <h2>Перетягніть файли або оберіть з пристрою</h2>
                <p>Підтримується багато файлів за раз. Максимальний розмір одного файлу — {{ $telegramUploadMaxMb }} MB.</p>
            </div>
            <div class="upload-hero-chips">
                <div class="upload-chip">
                    <span>Ліміт файла</span>
                    <strong>{{ $telegramUploadMaxMb }} MB</strong>
                </div>
                @if (! $canUseLocalStorage && $telegramStorageGroups->isEmpty() && $systemTelegramStorageAvailable)
                    <div class="upload-chip upload-chip-info">
                        <span>Системне сховище</span>
                        <strong>{{ $systemTelegramRemainingUploads }} / {{ $systemTelegramUploadLimit }}</strong>
                    </div>
                @endif
            </div>
        </header>

        <form class="upload-form-v2" action="{{ route('files.store') }}" method="post" enctype="multipart/form-data" data-upload-form>
            @csrf

            <label class="dropzone-v2" data-dropzone>
                <input type="file" name="files[]" multiple required data-upload-input>
                <div class="dropzone-v2-graphic" aria-hidden="true">
                    <svg viewBox="0 0 64 64" fill="none">
                        <rect x="8" y="14" width="48" height="40" rx="6" stroke="currentColor" stroke-width="2.5"/>
                        <path d="M20 14v-2a4 4 0 0 1 4-4h6l4 4h12a4 4 0 0 1 4 4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M32 26v18m-7-11 7-7 7 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="dropzone-v2-body">
                    <strong>Перетягніть файли сюди</strong>
                    <span>або <em>натисніть</em>, щоб обрати з пристрою</span>
                    <small>
                        @if ($canUseLocalStorage)
                            Локально або Telegram — залежно від обраного сховища нижче.
                        @else
                            Файли потраплять у Telegram-сховище.
                        @endif
                    </small>
                </div>
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
                <div class="upload-control upload-control-folder" data-upload-control>
                    <span class="upload-control-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                    </span>
                    <span class="upload-control-body">
                        <span class="upload-control-label">Папка</span>
                        <span class="upload-control-value" data-upload-control-value></span>
                    </span>
                    <span class="upload-control-chevron" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                    <select class="upload-control-select" id="folder_id" name="folder_id" data-upload-control-select aria-label="Папка">
                        <option value="">Без папки</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}" @selected($activeFolder?->id === $folder->id)>{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="upload-control upload-control-storage" data-upload-control>
                    <span class="upload-control-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.5 19a4.5 4.5 0 0 0 .5-8.97 7 7 0 0 0-13.74 2.05A4 4 0 0 0 5 19z"/>
                        </svg>
                    </span>
                    <span class="upload-control-body">
                        <span class="upload-control-label">Сховище</span>
                        <span class="upload-control-value" data-upload-control-value></span>
                    </span>
                    <span class="upload-control-chevron" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                    <select class="upload-control-select" id="telegram_storage_group_id" name="telegram_storage_group_id" data-upload-control-select aria-label="Сховище">
                        @if ($canUseLocalStorage)
                            <option value="">Локальне сховище</option>
                        @elseif ($telegramStorageGroups->isEmpty() && $systemTelegramStorageAvailable)
                            <option value="">Системне Telegram-сховище</option>
                        @elseif ($telegramStorageGroups->isEmpty())
                            <option value="">Telegram-сховище не налаштоване</option>
                        @else
                            <option value="">Оберіть Telegram-групу</option>
                        @endif
                        @foreach ($telegramStorageGroups as $storageGroup)
                            <option value="{{ $storageGroup->id }}" @selected((string) old('telegram_storage_group_id', $storageGroup->is_default ? $storageGroup->id : '') === (string) $storageGroup->id)>
                                Telegram: {{ $storageGroup->title }} · {{ $storageGroup->botToken?->name ?? 'бот' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (! $canUseLocalStorage && $telegramStorageGroups->isEmpty() && ! $systemTelegramStorageAvailable)
                <div class="upload-warning">
                    Власну Telegram-групу ще не підключено або системний ліміт вичерпано. Додайте власного бота і групу в налаштуваннях.
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

            <div class="upload-footer">
                <div class="upload-meta">
                    Метадані зберігаються в базі, а файли — у дозволеному сховищі.
                </div>
                <div class="upload-actions">
                    <button class="button" type="submit" data-upload-submit>
                        <span data-upload-submit-label>Завантажити</span>
                    </button>
                    @if (! $telegramStorageGroups->count() && ! $systemTelegramStorageAvailable)
                        <a class="button secondary" href="{{ route('telegram-settings.index') }}">Як прив’язати Telegram</a>
                    @elseif (! $telegramStorageGroups->count())
                        <a class="button secondary" href="{{ route('telegram-settings.index') }}">Власне Telegram-сховище</a>
                    @endif
                </div>
            </div>
        </form>
    </section>

    <section class="workspace">
        <aside class="sidebar-stack">
            <section class="panel sidebar-panel">
                <div class="panel-header sidebar-header">
                    <span class="sidebar-header-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Папки</h2>
                        <p>Створюйте окремі папки і завантажуйте файли в потрібний розділ.</p>
                    </div>
                </div>

                <form class="folder-form folder-form-v2" action="{{ route('folders.store') }}" method="post" data-ajax-form>
                    @csrf
                    <input class="field" type="text" name="name" value="{{ old('name') }}" placeholder="Назва папки" maxlength="100" required>
                    <button class="button" type="submit">Створити</button>
                </form>

                <div class="folder-list folder-list-v2" aria-label="Список папок">
                    <a class="folder-link {{ $folderFilter === 'all' ? 'active' : '' }}" href="{{ route('files.index') }}">
                        <span class="folder-link-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                                <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                            </svg>
                        </span>
                        <span class="folder-name">Усі файли</span>
                        <span class="folder-count">{{ $stats['total'] }}</span>
                    </a>
                    <a class="folder-link {{ $folderFilter === 'root' ? 'active' : '' }}" href="{{ route('files.index', ['folder' => 'root']) }}">
                        <span class="folder-link-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </span>
                        <span class="folder-name">Без папки</span>
                        <span class="folder-count">{{ $stats['root'] }}</span>
                    </a>

                    @foreach ($folders as $folder)
                        <div class="folder-row">
                            <a class="folder-link {{ $activeFolder?->id === $folder->id ? 'active' : '' }}" href="{{ route('files.index', ['folder' => $folder->id]) }}">
                                <span class="folder-link-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    </svg>
                                </span>
                                <span class="folder-name">{{ $folder->name }}</span>
                                <span class="folder-count">{{ $folder->files_count }}</span>
                            </a>
                            <div class="folder-actions">
                                @include('files.partials.folder-actions', ['folder' => $folder])
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </aside>

        <section class="panel" aria-label="Список файлів">
            <form class="filters" action="{{ route('files.index') }}" method="get" data-ajax-filter>
                @if ($folderFilter !== 'all')
                    <input type="hidden" name="folder" value="{{ $folderFilter }}">
                @endif
                <input type="hidden" name="view" value="{{ $display }}">
                <input class="field" type="search" name="search" value="{{ $search }}" placeholder="Пошук за назвою, MIME або розширенням">
                <select class="field" name="type">
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
                <button class="button secondary" type="submit">Фільтрувати</button>
            </form>

            <div class="file-view-bar">
                <span data-file-summary data-total="{{ $files->total() }}">Показано {{ $files->count() }} з {{ $files->total() }}</span>
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

            @if ($files->hasMorePages())
                <nav class="pagination" data-load-more aria-label="Завантаження додаткових файлів">
                    <span>Показано {{ min($files->currentPage() * $files->perPage(), $files->total()) }} з {{ $files->total() }}</span>
                    <a class="button secondary" href="{{ $files->nextPageUrl() }}" data-load-more-link>Завантажити ще</a>
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

                    refreshFilesPage(url.toString(), true);

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

                const link = event.target.closest('[data-load-more-link]');

                if (link) {
                    event.preventDefault();
                    loadMoreFiles(link);

                    return;
                }

                const navigationLink = event.target.closest('.folder-link, .view-toggle a');

                if (navigationLink && ! navigationLink.target) {
                    event.preventDefault();
                    refreshFilesPage(navigationLink.href, true);
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
            }

            function initUploadControls() {
                document.querySelectorAll('[data-upload-control]').forEach((control) => {
                    const select = control.querySelector('[data-upload-control-select]');
                    const valueSpan = control.querySelector('[data-upload-control-value]');

                    if (! select || ! valueSpan) {
                        return;
                    }

                    const sync = () => {
                        const option = select.options[select.selectedIndex];
                        valueSpan.textContent = option ? option.text : '';
                    };

                    sync();

                    if (! select.dataset.bound) {
                        select.dataset.bound = '1';
                        select.addEventListener('change', sync);
                    }
                });
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

                if (files.length === 0) {
                    wrapper.hidden = true;
                    list.innerHTML = '';

                    if (submitLabel) {
                        submitLabel.textContent = 'Завантажити';
                    }

                    return;
                }

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

            async function refreshFilesPage(url, pushHistory) {
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

                    replaceFilesPageFromHtml(html, response.url || url, pushHistory);
                } catch (error) {
                    showPageFlash(error.message || 'Не вдалося оновити сторінку.', true);
                }
            }

            async function loadMoreFiles(link) {
                const currentItems = document.querySelector('[data-file-items]');

                if (! currentItems) {
                    window.location.href = link.href;
                    return;
                }

                const originalText = link.textContent;
                link.textContent = 'Завантаження...';
                link.setAttribute('aria-disabled', 'true');

                try {
                    const response = await fetch(link.href, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nextItems = doc.querySelector('[data-file-items]');

                    if (! response.ok || ! nextItems) {
                        throw new Error(extractErrorFromHtml(html) || 'Не вдалося завантажити наступну сторінку.');
                    }

                    Array.from(nextItems.children).forEach((item) => currentItems.appendChild(item));

                    const currentPagination = document.querySelector('[data-load-more]');
                    const nextPagination = doc.querySelector('[data-load-more]');

                    if (currentPagination && nextPagination) {
                        currentPagination.replaceWith(nextPagination);
                    } else if (currentPagination) {
                        currentPagination.remove();
                    }

                    const summary = document.querySelector('[data-file-summary]');

                    if (summary) {
                        summary.textContent = `Показано ${currentItems.querySelectorAll('[data-file-item]').length} з ${summary.dataset.total}`;
                    }
                } catch (error) {
                    link.textContent = originalText;
                    link.removeAttribute('aria-disabled');
                    showPageFlash(error.message || 'Не вдалося завантажити файли.', true);
                }
            }

            function replaceFilesPageFromHtml(html, url, pushHistory) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const selectors = [
                    '[data-flash-area]',
                    '.stats',
                    '.upload-panel',
                    '.workspace',
                ];

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
