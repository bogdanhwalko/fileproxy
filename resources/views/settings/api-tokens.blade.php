@extends('layouts.site')

@section('title', 'API-токени — FileProxy')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-app-topbar title="FileProxy" subtitle="API-токени для зовнішніх інтеграцій" />

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
        <div class="panel-header compact">
            <h2>API-токени</h2>
            <p>Створіть токен для зовнішнього скрипта чи мобільного додатку. Токен показується <strong>один раз</strong> після створення — збережіть його одразу.</p>
        </div>

        <div class="guide-actions" style="margin: 12px 0 24px;">
            <a class="button" href="{{ route('docs.api') }}" target="_blank" rel="noopener">
                Документація API
            </a>
            <span>Огляд усіх endpoints, приклади на curl і PHP</span>
        </div>

        @if ($newToken)
            <div class="status" style="background: #064e3b; color: #d1fae5; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                <strong>Новий токен «{{ $newTokenName }}»</strong>
                <p style="margin: 8px 0;">Скопіюйте зараз — більше показано не буде.</p>
                <code style="display: block; padding: 12px; background: rgba(0,0,0,0.35); border-radius: 8px; word-break: break-all; font-family: monospace; user-select: all;">{{ $newToken }}</code>
                <p style="margin: 8px 0 0; font-size: 13px;">Передавайте у заголовку: <code>Authorization: Bearer &lt;токен&gt;</code></p>
            </div>
        @endif

        <form action="{{ route('api-tokens.store') }}" method="post" style="margin-bottom: 24px;">
            @csrf
            <div class="field-group">
                <label for="token_name">Назва токена</label>
                <input class="field" id="token_name" type="text" name="name" value="{{ old('name') }}" maxlength="100" placeholder="mobile-app, backup-script, ci-deploy" required>
            </div>
            <div class="bot-form-actions">
                <button class="button" type="submit">Створити токен</button>
                <span class="muted">Активних: {{ $tokens->count() }} / {{ $tokenLimit }}</span>
            </div>
        </form>

        <div class="table-wrap">
            <table class="compact-file-table">
                <thead>
                    <tr>
                        <th>Назва</th>
                        <th>Створено</th>
                        <th>Останнє використання</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tokens as $token)
                        <tr>
                            <td><strong>{{ $token->name }}</strong></td>
                            <td class="muted">{{ $token->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td class="muted">{{ $token->last_used_at?->format('d.m.Y H:i') ?? 'жодного разу' }}</td>
                            <td>
                                <form action="{{ route('api-tokens.destroy', $token->id) }}" method="post" onsubmit="return confirm('Відкликати цей токен? Усі запити з ним перестануть працювати.');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="button danger">Відкликати</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty">Поки що жодного активного токена. Створіть перший — і використовуйте FileProxy з власних скриптів.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
