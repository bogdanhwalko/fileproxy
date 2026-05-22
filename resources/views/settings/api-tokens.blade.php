@extends('layouts.site')

@section('title', 'API-токени — FileProxy')
@section('robots', 'noindex, nofollow')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/api-tokens.css') }}">
@endpush

@section('content')
    <x-app-topbar title="FileProxy" subtitle="API-токени для зовнішніх інтеграцій" />

    <div class="tokens-page">
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

        {{-- Hero --}}
        <section class="tokens-hero">
            <div class="tokens-hero-content">
                <span class="tokens-hero-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                    </svg>
                </span>
                <div class="tokens-hero-body">
                    <h1>API-токени</h1>
                    <p>Bearer-токени для скриптів, мобільних застосунків та CI/CD. Створений токен показується <strong>один раз</strong> — збережіть його в менеджері паролів.</p>
                </div>
                <div class="tokens-hero-actions">
                    <a class="button" href="{{ route('docs.api') }}" target="_blank" rel="noopener">
                        Документація API
                    </a>
                </div>
            </div>
        </section>

        {{-- Newly created token banner --}}
        @if ($newToken)
            <section class="token-banner" role="status" aria-live="polite">
                <header class="token-banner-head">
                    <span class="token-banner-head-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </span>
                    <strong>Токен <span class="token-banner-head-name">«{{ $newTokenName }}»</span> створено</strong>
                </header>
                <div class="token-banner-body">
                    <p class="token-banner-note">Скопіюйте токен зараз — після перезавантаження сторінки повторно його ніхто не побачить. Передавайте у заголовку <code>Authorization: Bearer …</code>.</p>

                    <div class="token-banner-code">
                        <code data-token-text>{{ $newToken }}</code>
                        <button type="button" class="token-banner-copy" data-token-copy>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="9" width="13" height="13" rx="2"/>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                            </svg>
                            <span data-copy-label>Скопіювати</span>
                        </button>
                    </div>

                    <div class="token-banner-curl-wrap">
                        <span class="token-banner-curl-label">Перевірте токен</span>
                        <pre class="token-banner-curl">curl -H "Authorization: Bearer {{ $newToken }}" {{ url('/api/v1/user') }}</pre>
                    </div>
                </div>
            </section>
        @endif

        {{-- Create form + quickstart --}}
        <div class="tokens-grid">
            <form class="tokens-create-card" action="{{ route('api-tokens.store') }}" method="post">
                @csrf
                <p class="tokens-create-card-title">Створити новий токен</p>
                <p class="tokens-create-card-desc">Дайте токену зрозумілу назву — щоб потім легко знайти і відкликати, якщо буде потрібно.</p>

                <div class="field-group">
                    <label for="token_name">Назва</label>
                    <input class="field" id="token_name" type="text" name="name"
                           value="{{ old('name') }}" maxlength="100"
                           placeholder="mobile-app, backup-script, ci-deploy" required>
                </div>

                <div class="tokens-create-card-footer">
                    <span class="tokens-create-card-counter @if($tokens->count() >= $tokenLimit) is-full @endif">
                        Активних: <strong>{{ $tokens->count() }}</strong> з <strong>{{ $tokenLimit }}</strong>
                    </span>
                    <button class="button" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Створити токен
                    </button>
                </div>
            </form>

            <aside class="tokens-quickstart">
                <div class="tokens-quickstart-head">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                    Quick start
                </div>
                <p>Усі ендпоінти під <code>/api/v1/</code>. Авторизація — bearer-токен у заголовку:</p>
                <pre>curl -H "Authorization: Bearer YOUR_TOKEN" \
     {{ url('/api/v1/files') }}</pre>
                <a class="tokens-quickstart-link" href="{{ route('docs.api') }}" target="_blank" rel="noopener">
                    Повна документація →
                </a>
            </aside>
        </div>

        {{-- Token list --}}
        <section class="token-list-section">
            <header class="token-list-header">
                <h2>Активні токени</h2>
                @if ($tokens->count() > 0)
                    <span class="token-list-header-count">{{ $tokens->count() }} {{ $tokens->count() === 1 ? 'токен' : 'токенів' }}</span>
                @endif
            </header>

            <div class="token-list">
                @forelse ($tokens as $token)
                    <article class="token-card">
                        <span class="token-card-avatar" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                            </svg>
                        </span>

                        <div class="token-card-body">
                            <div class="token-card-name">{{ $token->name }}</div>
                            <div class="token-card-meta">
                                <span class="token-pill">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    Створено {{ $token->created_at?->format('d.m.Y') ?? '—' }}
                                </span>
                                @if ($token->last_used_at)
                                    <span class="token-pill is-active">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12 6 12 12 16 14"/>
                                        </svg>
                                        Використано {{ $token->last_used_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="token-pill is-idle">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="12" y1="8" x2="12" y2="12"/>
                                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                                        </svg>
                                        Не використовувався
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="token-card-action">
                            <form action="{{ route('api-tokens.destroy', $token->id) }}" method="post"
                                  onsubmit="return confirm('Відкликати токен «{{ $token->name }}»? Усі запити з ним перестануть працювати.');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="button danger">Відкликати</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="token-empty">
                        <span class="token-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                            </svg>
                        </span>
                        <div>
                            <div class="token-empty-title">Поки що жодного токена</div>
                            <p>Створіть перший токен у формі вище — і використовуйте FileProxy зі своїх скриптів, мобільних додатків або CI/CD-пайплайнів.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const button = document.querySelector('[data-token-copy]');
            const source = document.querySelector('[data-token-text]');
            const label  = document.querySelector('[data-copy-label]');

            if (!button || !source) return;

            const originalLabel = label?.textContent || 'Скопіювати';

            button.addEventListener('click', async () => {
                const text = source.textContent.trim();

                const flash = () => {
                    if (label) label.textContent = 'Скопійовано';
                    button.classList.add('is-copied');
                    setTimeout(() => {
                        if (label) label.textContent = originalLabel;
                        button.classList.remove('is-copied');
                    }, 1600);
                };

                try {
                    await navigator.clipboard.writeText(text);
                    flash();
                } catch (e) {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); flash(); } catch (_) {}
                    document.body.removeChild(ta);
                }
            });
        })();
    </script>
@endpush
