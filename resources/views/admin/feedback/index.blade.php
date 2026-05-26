@extends('layouts.site')

@section('title', 'Звернення користувачів — адмінка')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-app-topbar title="FileProxy" subtitle="Звернення користувачів" />

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <section class="admin-feedback-hero">
        <div>
            <h1>Звернення користувачів</h1>
            <p>Усього: <strong>{{ $counts['all'] }}</strong> · Нових: <strong class="admin-feedback-new-count">{{ $counts['new'] }}</strong></p>
        </div>
        <a class="button secondary" href="{{ route('admin.users.index') }}">← До користувачів</a>
    </section>

    <section class="panel admin-feedback-filters">
        <form method="get" action="{{ route('admin.feedback.index') }}" class="admin-feedback-filter-form">
            <div class="field-group">
                <label for="filter-status">Статус</label>
                <select id="filter-status" class="field" name="status">
                    <option value="all" @selected($statusFilter === 'all')>Усі</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($statusFilter === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-group">
                <label for="filter-type">Тип</label>
                <select id="filter-type" class="field" name="type">
                    <option value="all" @selected($typeFilter === 'all')>Усі типи</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected($typeFilter === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-feedback-filter-actions">
                <button type="submit" class="button">Фільтрувати</button>
                @if ($statusFilter !== 'all' || $typeFilter !== 'all')
                    <a class="button secondary" href="{{ route('admin.feedback.index') }}">Скинути</a>
                @endif
            </div>
        </form>
    </section>

    <section class="admin-feedback-list">
        @forelse ($messages as $message)
            <article class="panel admin-feedback-card admin-feedback-card-{{ $message->status }}">
                <header class="admin-feedback-card-head">
                    <div class="admin-feedback-card-title">
                        <span class="admin-feedback-type admin-feedback-type-{{ $message->type }}">
                            {{ $message->type_label }}
                        </span>
                        <h3>{{ $message->subject }}</h3>
                    </div>
                    <div class="admin-feedback-card-meta">
                        <span class="admin-feedback-status admin-feedback-status-{{ $message->status }}">
                            {{ $message->status_label }}
                        </span>
                        <time datetime="{{ $message->created_at->toIso8601String() }}" title="{{ $message->created_at->format('d.m.Y H:i') }}">
                            {{ $message->created_at->diffForHumans() }}
                        </time>
                    </div>
                </header>

                <div class="admin-feedback-card-user">
                    @if ($message->user)
                        <a href="{{ route('admin.users.show', $message->user) }}">
                            <span class="admin-feedback-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($message->user->name, 0, 1)) ?: 'U' }}</span>
                            <strong>{{ $message->user->name }}</strong>
                            <span>{{ $message->user->email }}</span>
                        </a>
                    @else
                        <span class="admin-feedback-avatar admin-feedback-avatar-deleted" aria-hidden="true">?</span>
                        <em>Користувача видалено</em>
                    @endif

                    @if ($message->contact)
                        <div class="admin-feedback-contact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            {{ $message->contact }}
                        </div>
                    @endif
                </div>

                <div class="admin-feedback-card-message">{{ $message->message }}</div>

                <details class="admin-feedback-card-actions">
                    <summary>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                        Керування
                    </summary>
                    <form action="{{ route('admin.feedback.update', $message) }}" method="post" class="admin-feedback-update-form">
                        @csrf
                        @method('patch')
                        <div class="field-group">
                            <label>Статус</label>
                            <select class="field" name="status">
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" @selected($message->status === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field-group">
                            <label>Внутрішні нотатки</label>
                            <textarea class="field" name="admin_notes" rows="3" placeholder="Не показується користувачу">{{ $message->admin_notes }}</textarea>
                        </div>
                        <button type="submit" class="button">Зберегти</button>
                    </form>
                </details>

                @if ($message->admin_notes)
                    <div class="admin-feedback-notes">
                        <strong>Нотатки:</strong> {{ $message->admin_notes }}
                    </div>
                @endif

                @if ($message->user_agent || $message->ip)
                    <div class="admin-feedback-meta-foot">
                        @if ($message->ip)
                            <span>IP: <code>{{ $message->ip }}</code></span>
                        @endif
                        @if ($message->user_agent)
                            <span class="admin-feedback-ua" title="{{ $message->user_agent }}">
                                {{ \Illuminate\Support\Str::limit($message->user_agent, 60) }}
                            </span>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="panel admin-feedback-empty">
                <p>Звернень не знайдено за поточними фільтрами.</p>
            </div>
        @endforelse

        @if ($messages->hasPages())
            <div class="admin-feedback-pagination">
                {{ $messages->links() }}
            </div>
        @endif
    </section>
@endsection
