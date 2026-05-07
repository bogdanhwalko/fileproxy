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

    $links = [];

    if ($authUser) {
        if ($authUser->is_admin && $currentRoute !== 'admin.users.index' && ! str_starts_with((string) $currentRoute, 'admin.')) {
            $links[] = [
                'href' => route('admin.users.index'),
                'label' => 'Адмінка',
                'icon' => 'admin',
            ];
        }

        if ($currentRoute !== 'files.index') {
            $links[] = [
                'href' => route('files.index'),
                'label' => 'Кабінет',
                'icon' => 'folder',
            ];
        }

        if ($currentRoute !== 'stats.index') {
            $links[] = [
                'href' => route('stats.index'),
                'label' => 'Статистика',
                'icon' => 'stats',
            ];
        }

        if ($currentRoute !== 'telegram-settings.index') {
            $links[] = [
                'href' => route('telegram-settings.index'),
                'label' => 'Telegram-сховище',
                'icon' => 'telegram',
            ];
        }
    }
@endphp

<header class="topbar topbar-v2">
    <a class="brand" href="{{ $brandHref }}">
        <div class="brand-mark">FP</div>
        <div>
            <strong>{{ $title }}</strong>
            @if ($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
        </div>
    </a>

    <div class="nav-actions">
        @if ($showUserChip && $authUser)
            <span class="user-chip user-chip-v2">
                <span class="user-chip-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($authUser->name, 0, 1)) ?: 'U' }}</span>
                <span class="user-chip-name">{{ $authUser->name }}</span>
            </span>
        @endif

        {{ $slot }}

        @foreach ($links as $link)
            <a class="button secondary nav-button" href="{{ $link['href'] }}" aria-label="{{ $link['label'] }}">
                @switch($link['icon'])
                    @case('admin')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5z"/>
                        </svg>
                        @break
                    @case('folder')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                        @break
                    @case('stats')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                        @break
                    @case('telegram')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 2 11 13"/>
                            <path d="M22 2 15 22l-4-9-9-4z"/>
                        </svg>
                        @break
                    @case('users')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        @break
                    @case('back')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5"/>
                            <polyline points="12 19 5 12 12 5"/>
                        </svg>
                        @break
                @endswitch
                <span class="nav-button-label">{{ $link['label'] }}</span>
            </a>
        @endforeach

        @if ($showLogout && $authUser)
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
        @endif
    </div>
</header>
