@extends('layouts.site')

@section('title', $user->name.' - адмінка')

@section('content')
    <header class="topbar">
        <a class="brand" href="{{ route('admin.users.index') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>{{ $user->name }}</strong>
                <p>{{ $user->phone ?? 'phone not set' }} · {{ $user->email }}</p>
            </div>
        </a>

        <div class="nav-actions">
            <a class="button secondary" href="{{ route('admin.users.index') }}">До користувачів</a>
            <a class="button secondary" href="{{ route('files.index') }}">До файлів</a>
        </div>
    </header>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <section class="stats" aria-label="Користувач">
        <div class="stat">
            <span>Файли</span>
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
                    <h2>Папки користувача</h2>
                    <p>Фільтруйте файли за папками користувача.</p>
                </div>

                <div class="folder-list" aria-label="Папки користувача">
                    <a class="folder-link {{ $folderFilter === 'all' ? 'active' : '' }}" href="{{ route('admin.users.show', array_merge(['user' => $user], request()->except(['folder', 'page']))) }}">
                        <span class="folder-name">Усі файли</span>
                        <span class="folder-count">{{ $stats['total'] }}</span>
                    </a>
                    <a class="folder-link {{ $folderFilter === 'root' ? 'active' : '' }}" href="{{ route('admin.users.show', array_merge(['user' => $user], request()->except(['folder', 'page']), ['folder' => 'root'])) }}">
                        <span class="folder-name">Без папки</span>
                        <span class="folder-count">{{ $stats['root'] }}</span>
                    </a>

                    @foreach ($folders as $folder)
                        <a class="folder-link {{ $activeFolder?->id === $folder->id ? 'active' : '' }}" href="{{ route('admin.users.show', array_merge(['user' => $user], request()->except(['folder', 'page']), ['folder' => $folder->id])) }}">
                            <span class="folder-name">{{ $folder->name }}</span>
                            <span class="folder-count">{{ $folder->files_count }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Доступ</h2>
                    <p>Поточний статус користувача і швидкі дії.</p>
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

        <section class="panel" aria-label="Файли користувача">
            <form class="filters" action="{{ route('admin.users.show', $user) }}" method="get">
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
                <span>Показано {{ $files->count() }} з {{ $files->total() }}</span>
                <div class="view-toggle" aria-label="Вигляд файлів користувача">
                    <a class="button secondary {{ $display === 'table' ? 'active' : '' }}" href="{{ route('admin.users.show', array_merge(['user' => $user], request()->except(['page', 'view']), ['view' => 'table'])) }}">Таблиця</a>
                    <a class="button secondary {{ $display === 'grid' ? 'active' : '' }}" href="{{ route('admin.users.show', array_merge(['user' => $user], request()->except(['page', 'view']), ['view' => 'grid'])) }}">Плитки</a>
                </div>
            </div>

            @if ($display === 'grid')
                <div class="file-grid">
                    @forelse ($files as $file)
                        <article class="file-tile">
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
                                @if ($file->is_telegram)
                                    <span>Chat: {{ $file->telegram_chat_id }} · Message: {{ $file->telegram_message_id }}</span>
                                @endif
                            </div>
                            <div class="file-tile-actions">
                                @if ($file->is_previewable)
                                    <a class="button accent" href="{{ route('admin.users.files.preview', [$user, $file]) }}">Переглянути</a>
                                @endif
                                <a class="button secondary" href="{{ route('admin.users.files.download', [$user, $file]) }}">Скачати</a>
                                <form action="{{ route('admin.users.files.destroy', [$user, $file]) }}" method="post">
                                    @csrf
                                    @method('delete')
                                    <button class="button danger" type="submit">Видалити</button>
                                </form>
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
                                <th>Telegram</th>
                                <th>Дата</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($files as $file)
                                <tr>
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
                                    <td class="muted">{{ $file->is_telegram ? (($file->telegram_chat_id ?? '—').' / '.($file->telegram_message_id ?? '—')) : '—' }}</td>
                                    <td class="muted">{{ $file->created_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        <div class="file-row-actions">
                                            @if ($file->is_previewable)
                                                <a class="button accent" href="{{ route('admin.users.files.preview', [$user, $file]) }}">Переглянути</a>
                                            @endif
                                            <a class="button secondary" href="{{ route('admin.users.files.download', [$user, $file]) }}">Скачати</a>
                                            <form action="{{ route('admin.users.files.destroy', [$user, $file]) }}" method="post">
                                                @csrf
                                                @method('delete')
                                                <button class="button danger" type="submit">Видалити</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="empty">У користувача ще немає файлів.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($files->hasPages())
                <nav class="pagination" aria-label="Навігація файлами користувача">
                    <span>Сторінка {{ $files->currentPage() }} з {{ $files->lastPage() }}</span>
                    <div class="pagination-actions">
                        @if ($files->onFirstPage())
                            <span class="button secondary disabled">Назад</span>
                        @else
                            <a class="button secondary" href="{{ $files->previousPageUrl() }}">Назад</a>
                        @endif

                        @if ($files->hasMorePages())
                            <a class="button secondary" href="{{ $files->nextPageUrl() }}">Далі</a>
                        @else
                            <span class="button secondary disabled">Далі</span>
                        @endif
                    </div>
                </nav>
            @endif
        </section>
    </section>
@endsection
