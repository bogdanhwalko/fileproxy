@extends('layouts.site')

@section('title', $user->name.' — адмінка')
@section('robots', 'noindex, nofollow')

@include('partials.file-action-menu-script')

@section('content')
    <x-app-topbar
        :title="$user->name"
        :subtitle="($user->phone ?? 'phone not set').' · '.$user->email"
        :brandHref="route('admin.users.index')"
        :showUserChip="false"
    >
        <span class="user-chip user-chip-v2">
            <span class="user-chip-avatar" aria-hidden="true">{{ $user->is_admin ? 'A' : (mb_strtoupper(mb_substr($user->name, 0, 1)) ?: 'U') }}</span>
            <span class="user-chip-name">{{ $user->is_blocked ? 'Заблокований' : ($user->is_admin ? 'Адмін' : 'Активний') }}</span>
        </span>
        <a class="button secondary nav-button" href="{{ route('admin.users.index') }}" aria-label="До користувачів">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5"/>
                <polyline points="12 19 5 12 12 5"/>
            </svg>
            <span class="nav-button-label">До користувачів</span>
        </a>
    </x-app-topbar>

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

    <section class="stats stats-v2" aria-label="Статистика користувача">
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
        <div class="stat">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['folders'] }}</strong>
                <span>Папок</span>
            </div>
        </div>
        <div class="stat">
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
        <div class="stat">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['current'] }}</strong>
                <span>У поточному розділі</span>
            </div>
        </div>
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
                </header>

                <nav class="folders-list-v2" aria-label="Папки користувача">
                    <a class="folder-item folder-item-pinned {{ $folderFilter === 'all' ? 'is-active' : '' }}" href="{{ route('admin.users.show', array_merge(['user' => $user], request()->except(['folder', 'page']))) }}">
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

                    <a class="folder-item folder-item-pinned {{ $folderFilter === 'root' ? 'is-active' : '' }}" href="{{ route('admin.users.show', array_merge(['user' => $user], request()->except(['folder', 'page']), ['folder' => 'root'])) }}">
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
                        <a class="folder-item {{ $activeFolder?->id === $folder->id ? 'is-active' : '' }}" href="{{ route('admin.users.show', array_merge(['user' => $user], request()->except(['folder', 'page']), ['folder' => $folder->id])) }}">
                            <span class="folder-item-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                </svg>
                            </span>
                            <span class="folder-item-name">{{ $folder->name }}</span>
                            <span class="folder-item-count">{{ $folder->files_count }}</span>
                        </a>
                    @endforeach
                </nav>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Доступ</h2>
                    <p>Поточний статус і швидкі дії над акаунтом.</p>
                </div>

                <div class="settings-list">
                    <article class="settings-item">
                        <div>
                            <strong>{{ $user->is_blocked ? 'Заблокований' : 'Активний' }}</strong>
                            <span>{{ $user->is_admin ? 'Адміністратор' : 'Звичайний користувач' }}</span>
                        </div>
                        @if ($user->is_blocked)
                            <form action="{{ route('admin.users.unblock', $user) }}" method="post">
                                @csrf
                                <button class="button secondary" type="submit">Розблокувати</button>
                            </form>
                        @elseif ((int) $user->id !== (int) auth()->id())
                            <form action="{{ route('admin.users.block', $user) }}" method="post">
                                @csrf
                                <button class="button danger" type="submit">Заблокувати</button>
                            </form>
                        @endif
                    </article>
                </div>
            </section>
        </aside>

        <section class="panel" aria-label="Файли користувача" data-files-region>
            <form class="filters filters-v2" action="{{ route('admin.users.show', $user) }}" method="get">
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
                        $resetUrl = route('admin.users.show', $folderFilter !== 'all'
                            ? ['user' => $user, 'folder' => $folderFilter, 'view' => $display]
                            : ['user' => $user, 'view' => $display]);
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
                    <div class="view-toggle" aria-label="Вигляд списку файлів">
                        <a class="button secondary {{ $display === 'table' ? 'active' : '' }}" href="{{ route('admin.users.show', array_merge(['user' => $user], request()->except(['page', 'view', 'image_previews']), ['view' => 'table'])) }}">Таблиця</a>
                        <a class="button secondary {{ $display === 'grid' ? 'active' : '' }}" href="{{ route('admin.users.show', array_merge(['user' => $user], request()->except(['page', 'view']), ['view' => 'grid'])) }}">Плитки</a>
                        @if ($display === 'grid')
                            <a
                                class="button secondary {{ $imagePreviews ? 'active' : '' }}"
                                href="{{ route('admin.users.show', $imagePreviews
                                    ? array_merge(['user' => $user], request()->except(['page', 'image_previews']), ['view' => 'grid'])
                                    : array_merge(['user' => $user], request()->except(['page']), ['view' => 'grid', 'image_previews' => 1])) }}"
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
                        <article class="file-tile file-tile-status-{{ $file->status }}" data-file-item>
                            @if ($imagePreviews && $file->is_uploaded && $file->is_image)
                                <a class="file-tile-preview" href="{{ route('admin.users.files.preview', [$user, $file]) }}" aria-label="Відкрити {{ $file->original_name }}">
                                    <img src="{{ route('admin.users.files.inline', [$user, $file]) }}" alt="{{ $file->original_name }}" loading="eager" decoding="async">
                                </a>
                            @elseif ($imagePreviews)
                                <div class="file-tile-preview file-tile-preview-empty" aria-hidden="true">
                                    <span>{{ $file->type_label }}</span>
                                </div>
                            @endif
                            <div class="file-tile-head">
                                <span class="file-icon file-icon-cat-{{ $file->type_category }}">{{ $file->type_label }}</span>
                                <div class="file-tile-title">
                                    <strong title="{{ $file->original_name }}">{{ $file->original_name }}</strong>
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
                                @include('admin.partials.file-actions', ['user' => $user, 'file' => $file])
                            </div>
                        </article>
                    @empty
                        <div class="empty">У користувача ще немає файлів.</div>
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
                                <tr class="file-row-status-{{ $file->status }}" data-file-item>
                                    <td>
                                        <div class="file-table-name">
                                            <span class="file-icon file-icon-cat-{{ $file->type_category }}">{{ $file->type_label }}</span>
                                            <div class="file-table-title">
                                                <strong title="{{ $file->original_name }}">{{ $file->original_name }}</strong>
                                                <span>{{ $file->mime_type ?? 'unknown' }}</span>
                                                @if (! $file->is_uploaded)
                                                    <span class="file-status-badge file-status-badge-{{ $file->status }}">{{ $file->status_label }}</span>
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
                                            @include('admin.partials.file-actions', ['user' => $user, 'file' => $file])
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty">У користувача ще немає файлів.</div>
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

                <nav class="pagination-v2" aria-label="Навігація сторінок">
                    <span class="pagination-summary">
                        {{ $from }}–{{ $to }} з {{ $files->total() }}
                    </span>
                    <div class="pagination-pages">
                        @if ($files->onFirstPage())
                            <span class="button secondary disabled" aria-disabled="true">Назад</span>
                        @else
                            <a class="button secondary" href="{{ $files->previousPageUrl() }}" rel="prev">Назад</a>
                        @endif

                        @foreach ($pages as $p)
                            @if ($p === '...')
                                <span class="pagination-ellipsis">…</span>
                            @else
                                <a
                                    class="button secondary {{ $p === $current ? 'active' : '' }}"
                                    href="{{ $files->url($p) }}"
                                >{{ $p }}</a>
                            @endif
                        @endforeach

                        @if ($files->hasMorePages())
                            <a class="button secondary" href="{{ $files->nextPageUrl() }}" rel="next">Далі</a>
                        @else
                            <span class="button secondary disabled" aria-disabled="true">Далі</span>
                        @endif
                    </div>
                </nav>
            @endif
        </section>
    </section>
@endsection
