@extends('layouts.site')

@section('title', 'Telegram-сховище - FileProxy')

@section('content')
    <header class="topbar">
        <a class="brand" href="{{ route('files.index') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Боти і групи для зберігання файлів</p>
            </div>
        </a>

        <div class="nav-actions">
            @if (auth()->user()->is_admin)
                <a class="button secondary" href="{{ route('admin.users.index') }}">Адмінка</a>
            @endif
            <a class="button secondary" href="{{ route('files.index') }}">До файлів</a>
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button class="button secondary" type="submit">Вийти</button>
            </form>
        </div>
    </header>

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

    <section class="panel settings-guide">
        <div class="panel-header compact">
            <h2>Інструкція підключення</h2>
            <p>Створіть бота в BotFather, додайте його в Telegram-групу, отримайте chat_id і внесіть ці дані нижче.</p>
        </div>
        <div class="guide-steps">
            <div><strong>1. BotFather</strong><span>Скопіюйте token створеного бота.</span></div>
            <div><strong>2. Група</strong><span>Додайте бота в групу для файлів.</span></div>
            <div><strong>3. Chat ID</strong><span>Вкажіть ID у форматі на кшталт -1001234567890.</span></div>
            <div><strong>4. Завантаження</strong><span>Виберіть групу у формі файлів.</span></div>
        </div>
        <div class="settings-note">
            @if (auth()->user()->is_admin)
                Системних груп: {{ $globalDefaultGroupsCount }}. Позначте одну або кілька своїх груп як системні, щоб користувачі без власного сховища могли завантажити до 100 файлів.
            @else
                Якщо власну групу ще не додано, файли можна завантажувати в системне Telegram-сховище адміністратора, доки не буде використано ліміт 100 файлів.
            @endif
        </div>
    </section>

    <section class="settings-compact-grid">
        <section class="panel">
            <div class="panel-header compact">
                <h2>Боти</h2>
                <p>Token зберігається зашифрованим і використовується тільки для Telegram API.</p>
            </div>

            <form class="settings-form compact-settings-form" action="{{ route('telegram-settings.bots.store') }}" method="post">
                @csrf
                <div class="field-group">
                    <label for="bot_name">Назва</label>
                    <input class="field" id="bot_name" type="text" name="name" value="{{ old('name') }}" maxlength="100" required>
                </div>
                <div class="field-group">
                    <label for="bot_username">Username</label>
                    <input class="field" id="bot_username" type="text" name="username" value="{{ old('username') }}" maxlength="100" placeholder="@fileproxy_bot">
                </div>
                <div class="field-group">
                    <label for="bot_token">Bot token</label>
                    <input class="field" id="bot_token" type="password" name="token" maxlength="255" required>
                </div>
                <label class="checkbox compact-checkbox">
                    <input type="checkbox" name="is_default" value="1">
                    <span>Основний</span>
                </label>
                <button class="button" type="submit">Додати</button>
            </form>

            <div class="settings-table-wrap">
                <table class="settings-table">
                    <thead>
                        <tr>
                            <th>Бот</th>
                            <th>Token</th>
                            <th>Групи</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($botTokens as $bot)
                            <tr>
                                <td>
                                    <strong>{{ $bot->name }}</strong>
                                    <span>{{ $bot->username ?: 'Username не задано' }}</span>
                                    @if ($bot->is_default)
                                        <span class="badge success">Основний</span>
                                    @endif
                                </td>
                                <td><span class="token-mask">{{ $bot->masked_token }}</span></td>
                                <td>{{ $bot->storage_groups_count }}</td>
                                <td>
                                    <div class="table-actions">
                                        @unless ($bot->is_default)
                                            <form action="{{ route('telegram-settings.bots.default', $bot) }}" method="post">
                                                @csrf
                                                <button class="button secondary" type="submit">Основний</button>
                                            </form>
                                        @endunless
                                        <form action="{{ route('telegram-settings.bots.destroy', $bot) }}" method="post">
                                            @csrf
                                            @method('delete')
                                            <button class="button danger" type="submit">Видалити</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty compact-empty">Ботів ще немає.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header compact">
                <h2>Групи</h2>
                <p>Кожна група прив'язана до конкретного бота, який має бути учасником цієї групи.</p>
            </div>

            <form class="settings-form compact-settings-form" action="{{ route('telegram-settings.groups.store') }}" method="post">
                @csrf
                <div class="field-group">
                    <label for="telegram_bot_token_id">Бот</label>
                    <select class="field" id="telegram_bot_token_id" name="telegram_bot_token_id" required>
                        <option value="">Оберіть</option>
                        @foreach ($botTokens as $bot)
                            <option value="{{ $bot->id }}" @selected((string) old('telegram_bot_token_id') === (string) $bot->id)>{{ $bot->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-group">
                    <label for="group_title">Назва</label>
                    <input class="field" id="group_title" type="text" name="title" value="{{ old('title') }}" maxlength="100" required>
                </div>
                <div class="field-group">
                    <label for="chat_id">Chat ID</label>
                    <input class="field" id="chat_id" type="text" name="chat_id" value="{{ old('chat_id') }}" maxlength="128" placeholder="-1001234567890" required>
                </div>
                <label class="checkbox compact-checkbox">
                    <input type="checkbox" name="is_default" value="1">
                    <span>Основна</span>
                </label>
                <button class="button" type="submit">Додати</button>
            </form>

            <div class="settings-table-wrap">
                <table class="settings-table">
                    <thead>
                        <tr>
                            <th>Група</th>
                            <th>Бот</th>
                            <th>Chat ID</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($storageGroups as $group)
                            <tr>
                                <td>
                                    <strong>{{ $group->title }}</strong>
                                    <span class="badge-row">
                                        @if ($group->is_default)
                                            <span class="badge success">Основна</span>
                                        @endif
                                        @if ($group->is_global_default)
                                            <span class="badge accent">Системна</span>
                                        @endif
                                    </span>
                                </td>
                                <td>{{ $group->botToken?->name ?? 'Бот видалений' }}</td>
                                <td><span class="token-mask">{{ $group->chat_id }}</span></td>
                                <td>
                                    <div class="table-actions">
                                        @unless ($group->is_default)
                                            <form action="{{ route('telegram-settings.groups.default', $group) }}" method="post">
                                                @csrf
                                                <button class="button secondary" type="submit">Основна</button>
                                            </form>
                                        @endunless
                                        @if (auth()->user()->is_admin)
                                            @if ($group->is_global_default)
                                                <form action="{{ route('telegram-settings.groups.global-default.remove', $group) }}" method="post">
                                                    @csrf
                                                    @method('delete')
                                                    <button class="button secondary" type="submit">Прибрати системну</button>
                                                </form>
                                            @else
                                                <form action="{{ route('telegram-settings.groups.global-default', $group) }}" method="post">
                                                    @csrf
                                                    <button class="button accent" type="submit">Системна</button>
                                                </form>
                                            @endif
                                        @endif
                                        <form action="{{ route('telegram-settings.groups.destroy', $group) }}" method="post">
                                            @csrf
                                            @method('delete')
                                            <button class="button danger" type="submit">Видалити</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty compact-empty">Груп ще немає.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection
