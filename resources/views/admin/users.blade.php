@extends('layouts.site')

@section('title', 'Адмінка — користувачі')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-app-topbar title="Адмінка" subtitle="Користувачі, файли і доступ" :brandHref="route('admin.users.index')" />

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <section class="stats" aria-label="Адмін статистика">
        <div class="stat">
            <span>Користувачі</span>
            <strong>{{ $stats['users'] }}</strong>
        </div>
        <div class="stat">
            <span>Заблоковані</span>
            <strong>{{ $stats['blocked'] }}</strong>
        </div>
        <div class="stat">
            <span>Адміни</span>
            <strong>{{ $stats['admins'] }}</strong>
        </div>
        <div class="stat">
            <span>Файли</span>
            <strong>{{ $stats['files'] }}</strong>
        </div>
    </section>

    <section class="panel">
        <form class="filters" action="{{ route('admin.users.index') }}" method="get">
            <input class="field" type="search" name="search" value="{{ $search }}" placeholder="Пошук за нікнеймом, телефоном або email">
            <select class="field" name="status">
                <option value="all" @selected($status === 'all')>Усі статуси</option>
                <option value="active" @selected($status === 'active')>Активні</option>
                <option value="blocked" @selected($status === 'blocked')>Заблоковані</option>
            </select>
            <button class="button secondary" type="submit">Фільтрувати</button>
        </form>

        <div class="table-wrap">
            <table class="compact-file-table">
                <thead>
                    <tr>
                        <th>Користувач</th>
                        <th>Телефон</th>
                        <th>Статус</th>
                        <th>Файли</th>
                        <th>Боти</th>
                        <th>Групи</th>
                        <th>Створено</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="file-table-name">
                                    <span class="file-icon">{{ strtoupper(mb_substr($user->name, 0, 2)) }}</span>
                                    <div class="file-table-title">
                                        <strong title="{{ $user->name }}">{{ $user->name }}</strong>
                                        <span>{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="muted">{{ $user->phone ?? '—' }}</td>
                            <td>
                                <div class="badge-row">
                                    @if ($user->is_admin)
                                        <span class="badge accent">admin</span>
                                    @endif
                                    @if ($user->is_blocked)
                                        <span class="badge danger">blocked</span>
                                    @else
                                        <span class="badge success">active</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $user->files_count }}</td>
                            <td>{{ $user->telegram_bot_tokens_count }}</td>
                            <td>{{ $user->telegram_storage_groups_count }}</td>
                            <td class="muted">{{ $user->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                <div class="file-row-actions">
                                    <a class="button accent" href="{{ route('admin.users.show', $user) }}">Файли</a>
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
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty">Користувачів не знайдено.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <nav class="pagination" aria-label="Навігація користувачами">
                <span>Сторінка {{ $users->currentPage() }} з {{ $users->lastPage() }}</span>
                <div class="pagination-actions">
                    @if ($users->onFirstPage())
                        <span class="button secondary disabled">Назад</span>
                    @else
                        <a class="button secondary" href="{{ $users->previousPageUrl() }}">Назад</a>
                    @endif

                    @if ($users->hasMorePages())
                        <a class="button secondary" href="{{ $users->nextPageUrl() }}">Далі</a>
                    @else
                        <span class="button secondary disabled">Далі</span>
                    @endif
                </div>
            </nav>
        @endif
    </section>
@endsection
