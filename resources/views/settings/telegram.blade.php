@extends('layouts.site')

@section('title', 'Telegram-сховище — FileProxy')
@section('robots', 'noindex, nofollow')

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
            <h2>Інструкція підключення Telegram</h2>
            <p>Створіть бота, збережіть token у FileProxy і додайте бота в Telegram-групу. Група зʼявиться у списку сховищ автоматично — нічого писати не потрібно.</p>
        </div>
        <div class="guide-actions">
            <a class="button botfather-cta" href="{{ $botFatherNewBotUrl }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 2 11 13"/>
                    <path d="M22 2 15 22l-4-9-9-4z"/>
                </svg>
                Створити бота в BotFather
            </a>
            <span>Посилання відкриває @BotFather - інструмент для створення ботів</span>
        </div>
        <div class="guide-steps">
            <div><strong>1. Створіть бота</strong><span>Перейдіть за лінком до @BotFather, виконайте команду <code>/newbot</code> і заповніть необхідні поля. Запам’ятайте API token.</span></div>
            <div><strong>2. Додайте token у FileProxy</strong><span>Вставте отриманий token у форму «Боти» нижче. Це звʼяже бота з вашим акаунтом.</span></div>
            <div><strong>3. Додайте бота у Telegram-групу</strong><span>Створіть нову групу або оберіть існуючу і додайте туди вашого бота як учасника. Роль адміна не обовʼязкова.</span></div>
            <div><strong>4. Натисніть «Знайти групи»</strong><span>У блоці «Групи» нижче — велика кнопка <em>Знайти групи автоматично</em>. Один клік — і FileProxy сам знайде групи, до яких ви додали бота, і додасть їх у сховище.</span></div>
            <div><strong>5. Готово</strong><span>Поверніться до файлів, виберіть свою Telegram-групу в полі сховища і завантажте перший файл.</span></div>
        </div>
        @if (! auth()->user()->is_admin && $storageGroups->isEmpty())
            <div class="settings-note system-limit-note">
                @if ($systemTelegramStorageAvailable)
                    У вас ще немає власного Telegram-сховища. Тимчасово доступне загальне сховище адміністратора: використано {{ $systemTelegramUsedUploads }} з {{ $systemTelegramUploadLimit }}, залишилось {{ $systemTelegramRemainingUploads }} файлів.
                @else
                    У вас ще немає власного Telegram-сховища, а загальне сховище зараз недоступне або ліміт {{ $systemTelegramUploadLimit }} файлів уже вичерпано. Підключіть власного бота і групу за інструкцією вище.
                @endif
            </div>
        @endif
        <div class="settings-note">
            @if (auth()->user()->is_admin)
                Системних груп: {{ $globalDefaultGroupsCount }}. Позначте одну або кілька своїх груп як системні, щоб користувачі без власного сховища могли завантажити до 100 файлів.
            @else
                Якщо власну групу ще не додано, файли можна завантажувати в системне Telegram-сховище адміністратора, доки не буде використано ліміт 100 файлів.
            @endif
        </div>
    </section>

    <section class="settings-compact-grid">
        <section class="panel settings-panel-bots">
            <div class="panel-header compact">
                <h2>Боти</h2>
                <p>Token зберігається зашифрованим і використовується тільки для Telegram API.</p>
            </div>

            <form class="settings-form compact-settings-form bot-settings-form" action="{{ route('telegram-settings.bots.store') }}" method="post">
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
                            <tr data-bot-id="{{ $bot->id }}" data-group-count="{{ $bot->storage_groups_count }}" data-sync-url="{{ route('telegram-settings.bots.sync', $bot) }}">
                                <td>
                                    <strong>{{ $bot->name }}</strong>
                                    <span>{{ $bot->username ? '@'.$bot->username : 'Username не визначено' }}</span>
                                    @if ($bot->is_default)
                                        <span class="badge success">Основний</span>
                                    @endif
                                </td>
                                <td><span class="token-mask">{{ $bot->masked_token }}</span></td>
                                <td>{{ $bot->storage_groups_count }}</td>
                                <td>
                                    <div class="bot-actions">
                                        <form action="{{ route('telegram-settings.bots.repair', $bot) }}" method="post">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="bot-action-btn bot-action-repair"
                                                aria-label="Перезаписати webhook"
                                                title="Перезаписати webhook на цей бот"
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                                </svg>
                                            </button>
                                        </form>

                                        @unless ($bot->is_default)
                                            <form action="{{ route('telegram-settings.bots.default', $bot) }}" method="post">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="bot-action-btn bot-action-default"
                                                    aria-label="Зробити основним"
                                                    title="Зробити цього бота основним"
                                                >
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endunless

                                        <form action="{{ route('telegram-settings.bots.destroy', $bot) }}" method="post" onsubmit="return confirm('Видалити бота «{{ $bot->name }}»? Усі привʼязані групи теж будуть видалені.');">
                                            @csrf
                                            @method('delete')
                                            <button
                                                type="submit"
                                                class="bot-action-btn bot-action-delete"
                                                aria-label="Видалити бота"
                                                title="Видалити бота"
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <polyline points="3 6 5 6 21 6"/>
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                    <path d="M10 11v6M14 11v6"/>
                                                    <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                                                </svg>
                                            </button>
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

        <section class="panel settings-panel-groups">
            <div class="panel-header compact">
                <h2>Групи</h2>
                <p>Додайте бота у вашу Telegram-групу і натисніть кнопку нижче — група зʼявиться у списку. Форма знизу — для ручного додавання за chat_id.</p>
            </div>

            @if ($botTokens->isNotEmpty())
                <div class="groups-autosync" data-groups-autosync>
                    <div class="groups-autosync-text">
                        <strong>Не бачите свою групу?</strong>
                        <span>Спочатку додайте бота у Telegram-групу, а потім натисніть кнопку — FileProxy сам знайде і додасть групу.</span>
                    </div>
                    <button type="button" class="button groups-autosync-button" data-groups-search>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <line x1="21" y1="21" x2="16.5" y2="16.5"/>
                        </svg>
                        <span data-groups-search-label>Знайти групи автоматично</span>
                    </button>
                    <div class="groups-autosync-result" data-groups-search-result hidden></div>
                </div>
            @endif

            <form class="settings-form compact-settings-form group-settings-form" action="{{ route('telegram-settings.groups.store') }}" method="post">
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

@push('scripts')
    <script>
        // "Знайти групи автоматично" — single button that syncs all user's bots
        // and pulls in any groups they were added to.
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const button = document.querySelector('[data-groups-search]');
            const result = document.querySelector('[data-groups-search-result]');
            const label = document.querySelector('[data-groups-search-label]');

            if (! csrfToken || ! button) return;

            const ORIGINAL_LABEL = label?.textContent || 'Знайти групи';

            async function syncBot(row) {
                try {
                    const response = await fetch(row.dataset.syncUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (! response.ok) return null;

                    return await response.json().catch(() => null);
                } catch (e) {
                    return null;
                }
            }

            function showResult(message, kind = 'info') {
                if (! result) return;

                result.hidden = false;
                result.textContent = message;
                result.dataset.kind = kind;
            }

            button.addEventListener('click', async () => {
                const rows = document.querySelectorAll('tr[data-bot-id]');

                if (rows.length === 0) {
                    showResult('Спочатку додайте Telegram-бота у списку вище.', 'warn');
                    return;
                }

                button.disabled = true;
                button.classList.add('is-loading');
                if (label) label.textContent = 'Шукаю...';
                if (result) result.hidden = true;

                let totalCreated = 0;
                let totalProcessed = 0;
                let succeeded = 0;

                const results = await Promise.all(Array.from(rows).map((row) => syncBot(row)));

                for (const r of results) {
                    if (! r) continue;
                    succeeded++;
                    totalCreated += r.created_groups || 0;
                    totalProcessed += r.processed_updates || 0;
                }

                button.disabled = false;
                button.classList.remove('is-loading');
                if (label) label.textContent = ORIGINAL_LABEL;

                if (totalCreated > 0) {
                    showResult(`✓ Знайдено та додано груп: ${totalCreated}. Оновлюю сторінку...`, 'ok');
                    setTimeout(() => window.location.reload(), 800);
                    return;
                }

                if (succeeded === 0) {
                    showResult('Не вдалося звʼязатися з Telegram. Перевірте з’єднання та спробуйте ще раз.', 'warn');
                    return;
                }

                showResult(
                    'Нових груп не знайдено. Переконайтесь, що бот реально доданий у Telegram-групу як учасник.',
                    'info'
                );
            });
        })();

        document.addEventListener('click', async (event) => {
            const trigger = event.target.closest('[data-copy-text]');

            if (! trigger) {
                return;
            }

            event.preventDefault();

            const text = trigger.dataset.copyText;
            const original = trigger.querySelector('.bot-command-pill-text');
            const originalText = original?.textContent;

            const showCopied = () => {
                if (! original) return;
                original.textContent = 'Скопійовано ✓';
                trigger.classList.add('is-copied');
                setTimeout(() => {
                    original.textContent = originalText;
                    trigger.classList.remove('is-copied');
                }, 1400);
            };

            try {
                await navigator.clipboard.writeText(text);
                showCopied();
            } catch (e) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); showCopied(); } catch (_) {}
                document.body.removeChild(ta);
            }
        });
    </script>
@endpush
