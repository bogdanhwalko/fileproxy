@extends('layouts.site')

@section('title', 'API-токени — FileProxy')
@section('robots', 'noindex, nofollow')

@push('head')
    <style>
        .tokens-page { display: flex; flex-direction: column; gap: 20px; max-width: 920px; margin: 0 auto; }
        .tokens-shell { display: flex; flex-direction: column; gap: 22px; padding: 24px 26px; }

        .tokens-intro { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start; justify-content: space-between; }
        .tokens-intro > div { flex: 1 1 280px; min-width: 0; }
        .tokens-intro p { margin: 0; max-width: 56ch; color: var(--muted, #475569); line-height: 1.55; }
        .tokens-intro .button { white-space: nowrap; flex-shrink: 0; }

        .token-banner {
            background: linear-gradient(135deg, #064e3b, #047857);
            color: #ecfdf5; border-radius: 14px; padding: 20px 22px;
            display: flex; flex-direction: column; gap: 10px;
            box-shadow: 0 12px 40px -22px rgba(4, 120, 87, 0.6);
        }
        .token-banner strong { font-size: 15px; letter-spacing: 0.2px; }
        .token-banner .token-banner-note { font-size: 13px; opacity: 0.85; margin: 0; }
        .token-banner-code {
            display: flex; align-items: stretch; gap: 8px;
            background: rgba(0, 0, 0, 0.30); border-radius: 10px; padding: 4px;
        }
        .token-banner-code code {
            flex: 1; min-width: 0; padding: 10px 12px; background: transparent;
            color: #ecfdf5; font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 13px; line-height: 1.4; word-break: break-all; overflow-wrap: anywhere;
            user-select: all; white-space: pre-wrap;
        }
        .token-banner-copy {
            flex-shrink: 0; padding: 8px 14px; font-size: 13px;
            background: rgba(255,255,255,0.18); color: #ecfdf5;
            border: 0; border-radius: 8px; cursor: pointer; font-weight: 600;
            transition: background 0.15s;
        }
        .token-banner-copy:hover { background: rgba(255,255,255,0.28); }
        .token-banner-copy.is-copied { background: rgba(255,255,255,0.34); }
        .token-banner-curl {
            margin: 0; padding: 10px 12px; background: rgba(0,0,0,0.30);
            border-radius: 10px; font-family: ui-monospace, Consolas, monospace; font-size: 12.5px;
            color: #d1fae5; overflow-x: auto; white-space: pre;
        }

        .tokens-create-card {
            display: grid; gap: 14px;
            padding: 18px 20px;
            background: var(--surface-subtle, #f8fafc);
            border: 1px solid var(--line, #e2e8f0);
            border-radius: 12px;
        }
        .tokens-create-card-title { font-size: 13px; font-weight: 700; color: var(--muted, #64748b); text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
        .tokens-create-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px; }
        .tokens-create-row .field-group { flex: 2 1 280px; min-width: 220px; margin: 0; }
        .tokens-create-actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; flex: 0 0 auto; }

        .token-list { display: grid; gap: 10px; }
        .token-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px; align-items: center;
            padding: 14px 16px; border: 1px solid rgba(120, 120, 140, 0.20);
            border-radius: 12px; background: rgba(255, 255, 255, 0.03);
        }
        .token-card-body { min-width: 0; display: grid; gap: 4px; }
        .token-card-name { font-weight: 600; font-size: 15px; }
        .token-card-meta { display: flex; flex-wrap: wrap; gap: 6px 14px; font-size: 12.5px; color: #64748b; }
        .token-card-meta span { white-space: nowrap; }

        .token-empty {
            text-align: center; padding: 28px 18px; border: 1px dashed rgba(120,120,140,0.30);
            border-radius: 12px; color: #64748b;
        }

        @media (max-width: 640px) {
            .tokens-shell { padding: 18px 16px; }
            .token-card { grid-template-columns: 1fr; }
            .token-card form { width: 100%; }
            .token-card .button { width: 100%; }
            .token-banner-code { flex-direction: column; }
            .token-banner-copy { width: 100%; }
            .tokens-create-actions { width: 100%; justify-content: space-between; }
        }
    </style>
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
