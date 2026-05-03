@extends('layouts.site')

@section('title', 'Реєстрація - FileProxy')

@section('content')
    @php($telegramAuth = session('telegram_auth'))

    <nav class="site-nav">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Повернутися на головну</p>
            </div>
        </a>

        <div class="nav-actions">
            <a class="button secondary" href="{{ route('login') }}">Вхід</a>
        </div>
    </nav>

    <section class="panel auth-panel">
        <h1>Реєстрація</h1>
        <p>Створіть акаунт за номером телефону та підтвердьте його кодом із Telegram-бота.</p>

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

        @if ($telegramAuth)
            <div class="telegram-card">
                <strong>Код підтвердження</strong>

                @if ($telegramAuth['local_code'] ?? null)
                    <p>Локальний режим увімкнено. Використайте цей 6-значний код для реєстрації.</p>
                    <div class="local-code">{{ $telegramAuth['local_code'] }}</div>
                @else
                    <p>Перейдіть у Telegram-бота або надішліть йому команду нижче. Бот поверне 6-значний код.</p>

                    @if ($telegramAuth['bot_link'])
                        <a class="button accent" href="{{ $telegramAuth['bot_link'] }}" target="_blank" rel="noreferrer">Відкрити бота</a>
                    @endif

                    <code class="command-pill">{{ $telegramAuth['command'] }}</code>
                @endif
            </div>
        @endif

        <form class="auth-form" action="{{ route('register.store') }}" method="post">
            @csrf
            @if ($telegramAuth)
                <input type="hidden" name="challenge_token" value="{{ $telegramAuth['token'] }}">
            @endif

            <div class="field-group">
                <label for="nickname">Нікнейм <span class="required-mark">*</span></label>
                <input class="field" id="nickname" type="text" name="nickname" value="{{ old('nickname') }}" autocomplete="nickname" maxlength="255" required autofocus>
            </div>

            <div class="field-group">
                <label for="phone_local">Номер телефону <span class="required-mark">*</span></label>
                <input id="phone" type="hidden" name="phone" value="{{ old('phone') }}" data-phone-full>
                <div class="phone-input" data-phone-mask>
                    <span class="phone-prefix">+380</span>
                    <input
                        class="field"
                        id="phone_local"
                        type="tel"
                        inputmode="numeric"
                        autocomplete="tel-national"
                        placeholder="67 123-45-67"
                        maxlength="16"
                        data-phone-local
                        required
                    >
                </div>
            </div>

            @if ($telegramAuth)
                <div class="field-group">
                    <label for="telegram_code">Код підтвердження</label>
                    <input class="field" id="telegram_code" type="text" name="telegram_code" value="{{ $telegramAuth['local_code'] ?? '' }}" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
                </div>
            @endif

            <div class="auth-row">
                <button class="button" type="submit">{{ $telegramAuth ? 'Підтвердити реєстрацію' : 'Отримати код' }}</button>
                <a class="button link" href="{{ route('login') }}">У мене є акаунт</a>
            </div>
        </form>
    </section>
@endsection
