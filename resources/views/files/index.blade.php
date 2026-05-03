@extends('layouts.site')

@section('title', 'Файли - FileProxy')

@section('content')
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Завантаження, папки і швидке керування файлами</p>
            </div>
        </a>

        <div class="nav-actions">
            <span class="user-chip">{{ auth()->user()->name }}</span>
            @if (auth()->user()->is_admin)
                <a class="button secondary" href="{{ route('admin.users.index') }}">Адмінка</a>
            @endif
            <a class="button secondary" href="{{ route('telegram-settings.index') }}">Telegram-сховище</a>
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button class="button secondary" type="submit">Вийти</button>
            </form>
        </div>
    </header>

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

    <section class="stats" aria-label="Статистика сховища">
        <div class="stat">
            <span>Усього файлів</span>
            <strong>{{ $stats['total'] }}</strong>
        </div>
        <div class="stat">
            <span>Зайнято місця</span>
            <strong>{{ $stats['storage'] }}</strong>
        </div>
        <div class="stat">
            <span>Папки</span>
            <strong>{{ $stats['folders'] }}</strong>
        </div>
        <div class="stat">
            <span>У Telegram</span>
            <strong>{{ $stats['telegram'] }}</strong>
        </div>
        <div class="stat">
            <span>У поточному розділі</span>
            <strong>{{ $stats['current'] }}</strong>
        </div>
    </section>

    <section class="workspace">
        <aside class="sidebar-stack">
            <section class="panel">
                <div class="panel-header">
                    <h2>Папки</h2>
                    <p>Створюйте окремі папки і завантажуйте файли безпосередньо в потрібний розділ.</p>
                </div>

                <form class="folder-form" action="{{ route('folders.store') }}" method="post">
                    @csrf
                    <input class="field" type="text" name="name" value="{{ old('name') }}" placeholder="Назва папки" maxlength="100" required>
                    <button class="button" type="submit">Створити</button>
                </form>

                <div class="folder-list" aria-label="Список папок">
                    <a class="folder-link {{ $folderFilter === 'all' ? 'active' : '' }}" href="{{ route('files.index') }}">
                        <span class="folder-name">Усі файли</span>
                        <span class="folder-count">{{ $stats['total'] }}</span>
                    </a>
                    <a class="folder-link {{ $folderFilter === 'root' ? 'active' : '' }}" href="{{ route('files.index', ['folder' => 'root']) }}">
                        <span class="folder-name">Без папки</span>
                        <span class="folder-count">{{ $stats['root'] }}</span>
                    </a>

                    @foreach ($folders as $folder)
                        <div class="folder-row">
                            <a class="folder-link {{ $activeFolder?->id === $folder->id ? 'active' : '' }}" href="{{ route('files.index', ['folder' => $folder->id]) }}">
                                <span class="folder-name">{{ $folder->name }}</span>
                                <span class="folder-count">{{ $folder->files_count }}</span>
                            </a>
                            <form action="{{ route('folders.destroy', $folder) }}" method="post">
                                @csrf
                                @method('delete')
                                <button class="button danger folder-delete" type="submit" title="Видалити папку">x</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Нове завантаження</h2>
                    <p>Можна додати один або кілька файлів за раз. Один файл - до {{ $telegramUploadMaxMb }} MB.</p>
                </div>

                <form class="upload-form" action="{{ route('files.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="field-group upload-target">
                        <label for="folder_id">Папка для файлів</label>
                        <select class="field" id="folder_id" name="folder_id">
                            <option value="">Без папки</option>
                            @foreach ($folders as $folder)
                                <option value="{{ $folder->id }}" @selected($activeFolder?->id === $folder->id)>{{ $folder->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group upload-target">
                        <label for="telegram_storage_group_id">Сховище</label>
                        <select class="field" id="telegram_storage_group_id" name="telegram_storage_group_id">
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

                    @if (! $canUseLocalStorage && $telegramStorageGroups->isEmpty())
                        <div class="upload-meta">
                            @if ($systemTelegramStorageAvailable)
                                Буде використано системне Telegram-сховище адміністратора. Залишилось {{ $systemTelegramRemainingUploads }} з {{ $systemTelegramUploadLimit }} файлів.
                            @else
                                Власну Telegram-групу ще не підключено або системний ліміт вичерпано. Додайте власного бота і групу в налаштуваннях.
                            @endif
                        </div>
                    @endif

                    <label class="dropzone">
                        <span class="dropzone-inner">
                            <span class="dropzone-icon">+</span>
                            <strong>Оберіть файли</strong>
                            <input type="file" name="files[]" multiple required>
                            <p class="hint">
                                @if ($canUseLocalStorage)
                                    Файли зберігаються локально або в обраній Telegram-групі, а службові дані записуються в MariaDB.
                                @else
                                    Файли звичайних користувачів зберігаються тільки в Telegram-групі, а службові дані записуються в MariaDB.
                                @endif
                            </p>
                        </span>
                    </label>

                    <div class="upload-meta">
                        Підтримується багато файлів за раз. Ліміт одного файла відповідає Telegram Bot API: {{ $telegramUploadMaxMb }} MB для multipart-завантаження.
                    </div>

                    <div class="upload-actions">
                        <button class="button" type="submit">Завантажити</button>
                        @if (! $telegramStorageGroups->count() && ! $systemTelegramStorageAvailable)
                            <a class="button secondary" href="{{ route('telegram-settings.index') }}">Як прив’язати Telegram</a>
                        @elseif (! $telegramStorageGroups->count())
                            <a class="button secondary" href="{{ route('telegram-settings.index') }}">Власне Telegram-сховище</a>
                        @endif
                    </div>
                </form>
            </section>
        </aside>

        <section class="panel" aria-label="Список файлів">
            <form class="filters" action="{{ route('files.index') }}" method="get">
                @if ($folderFilter !== 'all')
                    <input type="hidden" name="folder" value="{{ $folderFilter }}">
                @endif
                <input type="hidden" name="view" value="{{ $display }}">
                <input class="field" type="search" name="search" value="{{ $search }}" placeholder="Пошук за назвою, MIME або розширенням">
                <select class="field" name="type">
                    <option value="all" @selected($type === 'all')>Усі типи</option>
                    <option value="images" @selected($type === 'images')>Зображення</option>
                    <option value="documents" @selected($type === 'documents')>Документи</option>
                    <option value="archives" @selected($type === 'archives')>Архіви</option>
                </select>
                <button class="button secondary" type="submit">Фільтрувати</button>
            </form>

            <div class="file-view-bar">
                <span data-file-summary data-total="{{ $files->total() }}">Показано {{ $files->count() }} з {{ $files->total() }}</span>
                <div class="view-toggle" aria-label="Вигляд списку файлів">
                    <a class="button secondary {{ $display === 'table' ? 'active' : '' }}" href="{{ route('files.index', array_merge(request()->except(['page', 'view']), ['view' => 'table'])) }}">Таблиця</a>
                    <a class="button secondary {{ $display === 'grid' ? 'active' : '' }}" href="{{ route('files.index', array_merge(request()->except(['page', 'view']), ['view' => 'grid'])) }}">Плитки</a>
                </div>
            </div>

            @if ($display === 'grid')
                <div class="file-grid" data-file-items>
                    @forelse ($files as $file)
                        <article class="file-tile" data-file-item>
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
                                @if ($file->is_previewable)
                                    <a class="button accent" href="{{ route('files.preview', $file) }}">Переглянути</a>
                                @endif
                                <a class="button secondary" href="{{ route('files.download', $file) }}">Скачати</a>
                                <form action="{{ route('files.destroy', $file) }}" method="post">
                                    @csrf
                                    @method('delete')
                                    <button class="button danger" type="submit">Видалити</button>
                                </form>
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
                                            @if ($file->is_previewable)
                                                <a class="button accent" href="{{ route('files.preview', $file) }}">Переглянути</a>
                                            @endif
                                            <a class="button secondary" href="{{ route('files.download', $file) }}">Скачати</a>
                                            <form action="{{ route('files.destroy', $file) }}" method="post">
                                                @csrf
                                                @method('delete')
                                                <button class="button danger" type="submit">Видалити</button>
                                            </form>
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
            const items = document.querySelector('[data-file-items]');

            if (! items) {
                return;
            }

            document.addEventListener('click', async (event) => {
                const link = event.target.closest('[data-load-more-link]');

                if (! link) {
                    return;
                }

                event.preventDefault();

                const originalText = link.textContent;
                link.textContent = 'Завантаження...';
                link.setAttribute('aria-disabled', 'true');

                try {
                    const response = await fetch(link.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nextItems = doc.querySelector('[data-file-items]');

                    if (! nextItems) {
                        window.location.href = link.href;
                        return;
                    }

                    Array.from(nextItems.children).forEach((item) => items.appendChild(item));

                    const currentPagination = document.querySelector('[data-load-more]');
                    const nextPagination = doc.querySelector('[data-load-more]');

                    if (currentPagination && nextPagination) {
                        currentPagination.replaceWith(nextPagination);
                    } else if (currentPagination) {
                        currentPagination.remove();
                    }

                    const summary = document.querySelector('[data-file-summary]');

                    if (summary) {
                        summary.textContent = `Показано ${items.querySelectorAll('[data-file-item]').length} з ${summary.dataset.total}`;
                    }
                } catch (error) {
                    link.textContent = originalText;
                    link.removeAttribute('aria-disabled');
                    window.location.href = link.href;
                }
            });
        })();
    </script>
@endpush
