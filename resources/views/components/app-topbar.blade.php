@props([
    'title' => 'FileProxy',
    'subtitle' => null,
    'brandHref' => null,
    'showUserChip' => true,
    'showLogout' => true,
    'currentRoute' => null,
])

@php
    $brandHref = $brandHref ?? (auth()->check() ? route('files.index') : route('home'));
    $currentRoute = $currentRoute ?? request()->route()?->getName();
    $authUser = auth()->user();

    // Primary nav — always visible (even current page) so users understand
    // where they are. Active state highlighted with emerald accent.
    $primaryNav = [];
    if ($authUser) {
        $primaryNav[] = [
            'href' => route('files.index'),
            'label' => 'Файли',
            'icon' => 'folder',
            'active' => $currentRoute === 'files.index'
                || str_starts_with((string) $currentRoute, 'files.'),
        ];
        $primaryNav[] = [
            'href' => route('stats.index'),
            'label' => 'Статистика',
            'icon' => 'stats',
            'active' => $currentRoute === 'stats.index',
        ];
        $primaryNav[] = [
            'href' => route('telegram-settings.index'),
            'label' => 'Telegram',
            'icon' => 'telegram',
            'active' => $currentRoute === 'telegram-settings.index'
                || str_starts_with((string) $currentRoute, 'telegram'),
        ];
    }
@endphp

@once
    @push('scripts')
        <script src="@vasset('js/user-menu.js')" defer></script>
    @endpush
@endonce

<header class="topbar topbar-v3">
    <a class="brand brand-v3" href="{{ $brandHref }}">
        <div class="brand-mark">FP</div>
        <div class="brand-text">
            <strong>{{ $title }}</strong>
            @if ($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
        </div>
    </a>

    @if ($authUser)
        <nav class="topbar-nav" aria-label="Основна навігація">
            @foreach ($primaryNav as $link)
                <a class="topbar-nav-link {{ $link['active'] ? 'is-active' : '' }}" href="{{ $link['href'] }}">
                    @switch($link['icon'])
                        @case('folder')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            </svg>
                            @break
                        @case('stats')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                            @break
                        @case('telegram')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 2 11 13"/>
                                <path d="M22 2 15 22l-4-9-9-4z"/>
                            </svg>
                            @break
                    @endswitch
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    <div class="topbar-actions">
        {{ $slot }}

        @if ($authUser)
            {{-- User dropdown: avatar opens menu with secondary items + logout --}}
            <details class="user-menu" data-user-menu>
                <summary class="user-menu-trigger" aria-label="Меню користувача">
                    <span class="user-menu-avatar" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($authUser->name, 0, 1)) ?: 'U' }}
                    </span>
                    @if ($showUserChip)
                        <span class="user-menu-name">{{ $authUser->name }}</span>
                    @endif
                    <svg class="user-menu-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </summary>

                <div class="user-menu-panel" role="menu">
                    <div class="user-menu-head">
                        <span class="user-menu-avatar user-menu-avatar-lg" aria-hidden="true">
                            {{ mb_strtoupper(mb_substr($authUser->name, 0, 1)) ?: 'U' }}
                        </span>
                        <div class="user-menu-head-text">
                            <strong>{{ $authUser->name }}</strong>
                            @if ($authUser->email)
                                <span>{{ $authUser->email }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="user-menu-list">
                        <a class="user-menu-item" href="{{ route('api-tokens.index') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="16 18 22 12 16 6"/>
                                <polyline points="8 6 2 12 8 18"/>
                            </svg>
                            <span>API-токени</span>
                        </a>
                        <a class="user-menu-item" href="{{ route('feedback.create') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                            </svg>
                            <span>Звʼязок з розробником</span>
                        </a>
                        @if ($authUser->is_admin)
                            <a class="user-menu-item user-menu-item-admin" href="{{ route('admin.users.index') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5z"/>
                                </svg>
                                <span>Адмінка</span>
                            </a>
                            <a class="user-menu-item user-menu-item-admin" href="{{ route('admin.feedback.index') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                            </svg>
                                <span>Звернення користувачів</span>
                            </a>
                        @endif
                    </div>

                    @if ($showLogout)
                        <form action="{{ route('logout') }}" method="post" class="user-menu-logout-form">
                            @csrf
                            <button class="user-menu-item user-menu-item-danger" type="submit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16 17 21 12 16 7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                <span>Вийти</span>
                            </button>
                        </form>
                    @endif
                </div>
            </details>
        @endif
    </div>
</header>
