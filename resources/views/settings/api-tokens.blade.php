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

        <section class="panel">
            <div class="tokens-shell">
                <div class="tokens-intro">
                    <div>
                        <h2 style="margin: 0 0 6px;">API-токени</h2>
                        <p>Створюйте bearer-токени для зовнішніх скриптів і застосунків. Кожен токен показується <strong>один раз</strong> після створення — збережіть його одразу.</p>
                    </div>
                    <a class="button secondary" href="{{ route('docs.api') }}" target="_blank" rel="noopener">
                        Документація API →
                    </a>
                </div>

                @if ($newToken)
                    <div class="token-banner" role="status" aria-live="polite">
                        <strong>Новий токен «{{ $newTokenName }}»</strong>
                        <p class="token-banner-note">Скопіюйте зараз — після перезавантаження сторінки повторно його ніхто не побачить.</p>
                        <div class="token-banner-code">
                            <code data-token-text>{{ $newToken }}</code>
                            <button type="button" class="token-banner-copy" data-token-copy>Скопіювати</button>
                        </div>
                        <pre class="token-banner-curl">curl -H "Authorization: Bearer {{ $newToken }}" {{ url('/api/v1/user') }}</pre>
                    </div>
                @endif

                <form class="tokens-create-card" action="{{ route('api-tokens.store') }}" method="post">
                    @csrf
                    <p class="tokens-create-card-title">Новий токен</p>
                    <div class="tokens-create-row">
                        <div class="field-group">
                            <label for="token_name">Назва</label>
                            <input class="field" id="token_name" type="text" name="name" value="{{ old('name') }}" maxlength="100" placeholder="mobile-app, backup-script, ci-deploy" required>
                        </div>
                        <div class="tokens-create-actions">
                            <button class="button" type="submit">Створити</button>
                            <span class="muted">{{ $tokens->count() }} / {{ $tokenLimit }}</span>
                        </div>
                    </div>
                </form>

                <div class="token-list">
                    @forelse ($tokens as $token)
                        <article class="token-card">
                            <div class="token-card-body">
                                <div class="token-card-name">{{ $token->name }}</div>
                                <div class="token-card-meta">
                                    <span>Створено: {{ $token->created_at?->format('d.m.Y H:i') ?? '—' }}</span>
                                    <span>Останнє використання: {{ $token->last_used_at?->diffForHumans() ?? 'жодного разу' }}</span>
                                </div>
                            </div>
                            <form action="{{ route('api-tokens.destroy', $token->id) }}" method="post" onsubmit="return confirm('Відкликати цей токен? Усі запити з ним перестануть працювати.');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="button danger">Відкликати</button>
                            </form>
                        </article>
                    @empty
                        <div class="token-empty">
                            Поки що жодного активного токена. Створіть перший — і використовуйте FileProxy зі своїх скриптів, мобільних додатків чи CI.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const button = document.querySelector('[data-token-copy]');
            const source = document.querySelector('[data-token-text]');

            if (!button || !source) return;

            const original = button.textContent;

            button.addEventListener('click', async () => {
                const text = source.textContent.trim();

                const flash = () => {
                    button.textContent = 'Скопійовано ✓';
                    button.classList.add('is-copied');
                    setTimeout(() => {
                        button.textContent = original;
                        button.classList.remove('is-copied');
                    }, 1500);
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
