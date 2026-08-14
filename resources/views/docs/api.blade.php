@extends('layouts.site')

@section('title', 'API-документація — FileProxy')
@section('description', 'Документація REST API FileProxy: автентифікація, ендпоінти для файлів, папок і публічних посилань. Приклади на curl і PHP.')

@push('head')
    <link rel="stylesheet" href="@vasset('css/docs.css')">
@endpush

@section('content')
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>REST API · v1</p>
            </div>
        </a>

        <div class="nav-actions">
            @auth
                <a class="button secondary" href="{{ route('files.index') }}">Кабінет</a>
                <a class="button" href="{{ route('api-tokens.index') }}">Мої токени</a>
            @else
                <a class="button secondary" href="{{ route('login') }}">Увійти</a>
                <a class="button" href="{{ route('register') }}">Зареєструватись</a>
            @endauth
        </div>
    </header>

    <div class="docs-page">
        <div class="docs-wrap">
            <aside class="docs-aside" aria-label="Зміст документації">
                <span class="docs-aside-title">Зміст</span>
                <ul>
                    <li><a href="#auth">Автентифікація</a></li>
                    <li class="docs-aside-sub"><a href="#auth-phone">Логін через API (телефон)</a></li>
                    <li><a href="#errors">Формат помилок</a></li>
                    <li><a href="#rate-limits">Ліміти</a></li>
                    <li><a href="#user">User</a></li>
                    <li><a href="#files">Файли</a></li>
                    <li class="docs-aside-sub"><a href="#files-list">Список</a></li>
                    <li class="docs-aside-sub"><a href="#files-show">Метадані</a></li>
                    <li class="docs-aside-sub"><a href="#files-content">Скачати</a></li>
                    <li class="docs-aside-sub"><a href="#files-upload">Upload</a></li>
                    <li class="docs-aside-sub"><a href="#files-tags">Теги</a></li>
                    <li class="docs-aside-sub"><a href="#files-bulk">Масові дії</a></li>
                    <li class="docs-aside-sub"><a href="#files-archive">Архів (zip)</a></li>
                    <li class="docs-aside-sub"><a href="#files-delete">Видалити</a></li>
                    <li><a href="#folders">Папки</a></li>
                    <li class="docs-aside-sub"><a href="#folders-password">Пароль папки</a></li>
                    <li><a href="#telegram-groups">Telegram-групи</a></li>
                    <li><a href="#shares">Шеринг</a></li>
                    <li><a href="#stats">Статистика</a></li>
                    <li><a href="#examples">Приклади</a></li>
                </ul>
            </aside>

            <main class="docs-main">
                <div class="docs-hero">
                    <span class="docs-hero-eyebrow">API Reference · v1</span>
                    <h1>FileProxy API</h1>
                    <p class="docs-hero-lead">REST-інтерфейс для керування файлами, папками та публічними посиланнями. Автентифікація — bearer-токени (Laravel Sanctum). Усі відповіді — JSON, помилки — англійською.</p>
                    <div class="docs-hero-meta">
                        <div class="docs-hero-meta-item">
                            <span class="docs-hero-meta-label">Base URL</span>
                            <span class="docs-hero-meta-value">{{ url('/api/v1') }}</span>
                        </div>
                        <div class="docs-hero-meta-item">
                            <span class="docs-hero-meta-label">Версія</span>
                            <span class="docs-hero-meta-value">v1</span>
                        </div>
                        <div class="docs-hero-meta-item">
                            <span class="docs-hero-meta-label">Формат</span>
                            <span class="docs-hero-meta-value">application/json</span>
                        </div>
                        <div class="docs-hero-meta-item">
                            <span class="docs-hero-meta-label">Аутентифікація</span>
                            <span class="docs-hero-meta-value">Bearer Token</span>
                        </div>
                    </div>
                </div>

                {{-- ============ AUTH ============ --}}
                <section class="docs-section" id="auth">
                    <h2>Автентифікація</h2>
                    <p>Усі запити до <code>/api/v1/*</code> вимагають заголовок <code>Authorization: Bearer &lt;token&gt;</code>. Токен створюється в кабінеті.</p>

                    <div class="docs-cta">
                        @auth
                            <a class="button" href="{{ route('api-tokens.index') }}">Створити API-токен</a>
                            <span class="muted">або відкрийте «Налаштування → API»</span>
                        @else
                            <a class="button" href="{{ route('login') }}">Увійти</a>
                            <a class="button secondary" href="{{ route('register') }}">Зареєструватись</a>
                        @endauth
                    </div>

                    <div class="docs-callout">
                        <span class="docs-callout-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </span>
                        <div>
                            <strong>Важливо.</strong> Токен показується <strong>один раз</strong>. Збережіть його в менеджері паролів або зашифрованому сховищі — відкликати можна будь-коли на сторінці токенів.
                        </div>
                    </div>

                    <p>Максимум <strong>10 активних токенів</strong> на акаунт — створення 11-го поверне <code>422</code> (для сторінки токенів) або <code>403</code> (для логіну нижче), доки ви не відкличете інший. Токени створюються <strong>без строку дії</strong>: вони не «прострочуються» самі, лише відкликаються вручну.</p>

                    <h3 id="auth-phone">Логін через API (телефон) — для мобільних застосунків</h3>
                    <p>Якщо клієнт (наприклад, мобільний застосунок) не може попросити користувача зайти у вебкабінет і скопіювати токен вручну, він може отримати токен напряму — тим самим способом, що й вебсайт: номер телефону → код у Telegram-боті → обмін коду на токен. Обидва ендпоінти <strong>без автентифікації</strong> (саме вони її й видають) і мають той самий ліміт, що й форма входу на сайті: 5 запитів/хв на IP і 5 запитів/хв на номер телефону.</p>

                    <article class="docs-endpoint is-post has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-path">/api/v1/auth/login</span>
                            <span class="docs-endpoint-title">Крок 1 — запросити код</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Body JSON: <code>{ "phone": "+380671234567" }</code></p>
                            <p>Не розкриває, чи існує акаунт із цим номером — завжди повертає челлендж. Реальний код надсилається користувачу в Telegram (боту треба надіслати <code>/start</code> за посиланням <code>bot_link</code>, або поділитися контактом, якщо цей Telegram-чат ще не підтверджував цей номер раніше).</p>
<pre><code>{
  "challenge_token": "aBcD1234...",
  "bot_link": "https://t.me/your_bot?start=fileproxy_aBcD1234...",
  "expires_in": 600,
  "local_code": null
}</code></pre>
                            <p class="muted"><code>local_code</code> — лише в локальному/dev-режимі сервера (поза продакшеном), щоб не ганяти реальний Telegram під час розробки.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint is-post has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-path">/api/v1/auth/verify</span>
                            <span class="docs-endpoint-title">Крок 2 — підтвердити код і отримати токен</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Body JSON:</p>
<pre><code>{
  "phone": "+380671234567",
  "code": "123456",
  "challenge_token": "aBcD1234...",
  "device_name": "iPhone 15 Pro"
}</code></pre>
                            <table class="docs-table">
                                <thead><tr><th>Поле</th><th>Тип</th><th>Опис</th></tr></thead>
                                <tbody>
                                    <tr><td>phone</td><td>string, required</td><td>Той самий номер, що й у кроці 1.</td></tr>
                                    <tr><td>code</td><td>string, required</td><td>6-значний код із Telegram.</td></tr>
                                    <tr><td>challenge_token</td><td>string, optional</td><td><code>challenge_token</code> із кроку 1. Не обовʼязковий для успіху (код і так звіряється по номеру), але потрібен для точного трекінгу спроб.</td></tr>
                                    <tr><td>device_name</td><td>string, optional</td><td>Назва токена (напр. модель пристрою) — за замовчуванням «mobile-app».</td></tr>
                                </tbody>
                            </table>
                            <p>Відповідь <code>201</code>:</p>
<pre><code>{
  "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ...",
  "token_type": "Bearer",
  "user": { "id": 1, "name": "Bohdan", "phone": "+380671234567", "is_admin": false }
}</code></pre>
                            <p class="muted"><code>token</code> — так само, як і токен зі сторінки налаштувань, показується лише в цій відповіді. Зберігайте одразу.</p>
                            <table class="docs-table">
                                <thead><tr><th>Код</th><th>Причина</th></tr></thead>
                                <tbody>
                                    <tr><td>422</td><td>Невірний номер, код, акаунт не існує або заблокований (одне узагальнене повідомлення — анти-енумерація)</td></tr>
                                    <tr><td>403</td><td>Досягнуто ліміт 10 токенів на акаунт</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </section>

                {{-- ============ ERRORS ============ --}}
                <section class="docs-section" id="errors">
                    <h2>Формат помилок</h2>
                    <p>Будь-яка помилка повертається в єдиному форматі:</p>

                    <div class="docs-example">
                        <div class="docs-example-head">
                            <span class="docs-example-lang">JSON</span>
                            <span>Структура відповіді з помилкою</span>
                        </div>
<pre><code>{
  "message": "Human-readable description in English",
  "errors": {
    "field_name": ["Specific validation error"]
  }
}</code></pre>
                    </div>

                    <h3>Коди статусу</h3>
                    <div class="docs-status-grid">
                        <div class="docs-status-item"><span class="docs-status-code c-401">401</span><div>Token відсутній, невірний або відкликаний (токени не мають строку дії — лише ручне відкликання)</div></div>
                        <div class="docs-status-item"><span class="docs-status-code c-403">403</span><div>Акаунт заблоковано / перевищено системний ліміт</div></div>
                        <div class="docs-status-item"><span class="docs-status-code c-404">404</span><div>Ресурс не існує або не належить вам</div></div>
                        <div class="docs-status-item"><span class="docs-status-code c-409">409</span><div>Конфлікт — інше завантаження вже триває</div></div>
                        <div class="docs-status-item"><span class="docs-status-code c-422">422</span><div>Помилка валідації — деталі у полі <code>errors</code></div></div>
                        <div class="docs-status-item"><span class="docs-status-code c-429">429</span><div>Rate limit. Перевірте <code>X-RateLimit-*</code></div></div>
                        <div class="docs-status-item"><span class="docs-status-code c-500">500</span><div>Несподівана помилка сервера</div></div>
                    </div>
                </section>

                {{-- ============ RATE LIMITS ============ --}}
                <section class="docs-section" id="rate-limits">
                    <h2>Ліміти</h2>
                    <table class="docs-table">
                        <thead><tr><th>Ендпоінт</th><th>Ліміт</th></tr></thead>
                        <tbody>
                            <tr><td>Загальний</td><td>60 запитів / хвилину на акаунт</td></tr>
                            <tr><td>POST /files <em>(upload)</em></td><td>30 запитів / хвилину на акаунт</td></tr>
                            <tr><td>POST /auth/login, /auth/verify</td><td>5 запитів / хвилину на IP <strong>і</strong> 5 / хвилину на номер телефону (окремо, до автентифікації)</td></tr>
                        </tbody>
                    </table>
                    <p class="muted">Ліміт рахується на весь акаунт, а не на окремий токен — усі ваші токени ділять один і той самий ліміт запитів. Виняток — <code>/auth/*</code>, де акаунта (і токена) ще немає, тому ліміт рахується по IP і номеру телефону.</p>
                </section>

                {{-- ============ USER ============ --}}
                <section class="docs-section" id="user">
                    <h2>Поточний користувач</h2>

                    <article class="docs-endpoint is-get has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-path">/api/v1/user</span>
                            <span class="docs-endpoint-title">Дані поточного юзера</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Відповідь:</p>
<pre><code>{
  "id": 1,
  "name": "Bohdan",
  "phone": "+380...",
  "is_admin": false
}</code></pre>
                        </div>
                    </article>
                </section>

                {{-- ============ FILES ============ --}}
                <section class="docs-section" id="files">
                    <h2>Файли</h2>

                    <article class="docs-endpoint is-get has-body" id="files-list">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-path">/api/v1/files</span>
                            <span class="docs-endpoint-title">Список файлів</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Підтримує пагінацію та фільтри:</p>
                            <table class="docs-table">
                                <thead><tr><th>Параметр</th><th>Тип</th><th>Опис</th></tr></thead>
                                <tbody>
                                    <tr><td>folder_id</td><td>int / "root"</td><td>Фільтр по папці. <code>root</code> — файли в корені.</td></tr>
                                    <tr><td>search</td><td>string</td><td>Пошук по імені, MIME, розширенню.</td></tr>
                                    <tr><td>storage_driver</td><td>local / telegram</td><td>Тип сховища.</td></tr>
                                    <tr><td>date_from</td><td>YYYY-MM-DD</td><td>Від цієї дати.</td></tr>
                                    <tr><td>date_to</td><td>YYYY-MM-DD</td><td>До цієї дати (включно).</td></tr>
                                    <tr><td>per_page</td><td>int (1–100)</td><td>Розмір сторінки. За замовчуванням 20.</td></tr>
                                    <tr><td>page</td><td>int</td><td>Номер сторінки.</td></tr>
                                </tbody>
                            </table>
                            <p>Відповідь — стандартна Laravel-пагінація: <code>data</code> (масив файлів), <code>meta</code> (current_page, last_page, total), <code>links</code>.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint is-get has-body" id="files-show">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-path">/api/v1/files/{id}</span>
                            <span class="docs-endpoint-title">Метадані одного файлу</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Повертає одну сутність <code>ManagedFile</code> з повним набором полів, тегами і <code>share</code>-блоком, якщо ввімкнено публічний доступ.</p>

                            <div class="docs-example">
                                <div class="docs-example-head">
                                    <span class="docs-example-lang">JSON</span>
                                    <span>Response</span>
                                </div>
<pre><code>{
  "id": 123,
  "original_name": "report.pdf",
  "mime_type": "application/pdf",
  "extension": "pdf",
  "size": 245678,
  "human_size": "239.9 KB",
  "storage_driver": "telegram",
  "storage_label": "Telegram",
  "folder_id": 5,
  "folder_name": "Archive",
  "is_image": false,
  "is_text": false,
  "is_previewable": true,
  "is_telegram": true,
  "status": "uploaded",
  "status_label": "Завантажено",
  "upload_failure_reason": null,
  "tags": [
    { "id": 3, "name": "work" },
    { "id": 7, "name": "invoices" }
  ],
  "telegram": {
    "storage_group_id": 2,
    "storage_group_title": "My storage",
    "chat_id": "-1001234567890",
    "message_id": 456
  },
  "share": null,
  "created_at": "2026-08-01T10:15:00+00:00",
  "updated_at": "2026-08-01T10:15:00+00:00",
  "links": {
    "self": "{{ url('/api/v1/files/123') }}",
    "content": "{{ url('/api/v1/files/123/content') }}"
  }
}</code></pre>
                            </div>
                            <p class="muted"><code>telegram</code> присутнє лише коли <code>is_telegram</code> — true. <code>tags</code> завжди присутнє (порожній масив, якщо тегів немає).</p>
                        </div>
                    </article>

                    <article class="docs-endpoint is-get has-body" id="files-content">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-path">/api/v1/files/{id}/content</span>
                            <span class="docs-endpoint-title">Скачати файл</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Повертає файл з оригінальним MIME та <code>Content-Disposition: attachment</code>. Безпечно для будь-яких типів — заголовок <code>X-Content-Type-Options: nosniff</code> блокує MIME-sniffing.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint is-post has-body" id="files-upload">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-path">/api/v1/files</span>
                            <span class="docs-endpoint-title">Upload</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Multipart form-data. Поля:</p>
                            <table class="docs-table">
                                <thead><tr><th>Поле</th><th>Тип</th><th>Опис</th></tr></thead>
                                <tbody>
                                    <tr><td>file <em>або</em> files[]</td><td>file</td><td>До 25 файлів за один запит, кожен ≤ 50 MB.</td></tr>
                                    <tr><td>folder_id</td><td>int, optional</td><td>ID вашої папки.</td></tr>
                                    <tr><td>telegram_storage_group_id</td><td>int, optional</td><td>ID вашої Telegram-групи. Якщо не передати — файл піде у системне Telegram-сховище (для не-адмінів) або локально (для адмінів).</td></tr>
                                </tbody>
                            </table>
                            <p>Відповідь <code>201 Created</code> з масивом створених файлів. Якщо завантаження в Telegram — статус буде <code>pending</code> до завершення фонового джобу.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint is-patch has-body" id="files-tags">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-patch">PATCH</span>
                            <span class="docs-endpoint-path">/api/v1/files/{id}/tags</span>
                            <span class="docs-endpoint-title">Замінити теги файлу</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Повна заміна (sync), не додавання — попередній набір тегів файлу видаляється. Максимум 20 тегів за запит (зайві мовчки відкидаються), кожна назва — до 64 символів. Неіснуючі теги створюються автоматично.</p>
                            <p>Body JSON — масив назв або один рядок, розділений комою/крапкою з комою/переносом рядка:</p>
<pre><code>{ "tags": ["work", "invoices"] }
// або
{ "tags": "work, invoices" }</code></pre>
                            <p>Відповідь — оновлена сутність файлу (як у <a href="#files-show">GET /files/{id}</a>) з полем <code>tags</code>.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint is-post has-body" id="files-bulk">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-path">/api/v1/files/bulk-delete</span>
                            <span class="docs-endpoint-title">Масове видалення</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Body JSON: <code>{ "ids": [1, 2, 3] }</code> — до 200 id за запит. Id, що вам не належать, просто пропускаються (без помилки).</p>
<pre><code>{ "message": "Deleted 3 file(s), 0 failed.", "deleted": 3, "failed": 0 }</code></pre>
                        </div>
                    </article>
                    <article class="docs-endpoint is-post has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-path">/api/v1/files/bulk-move</span>
                            <span class="docs-endpoint-title">Масове переміщення</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Body JSON: <code>{ "ids": [1, 2, 3], "folder_id": 5 }</code> — до 500 id за запит. Опустіть або передайте <code>null</code> у <code>folder_id</code>, щоб перемістити в корінь.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint is-get has-body" id="files-archive">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-path">/api/v1/files/archive</span>
                            <span class="docs-endpoint-title">Скачати архівом (zip)</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Приймає ті самі фільтри, що й список файлів: <code>search</code>, <code>type</code>, <code>folder</code> (id, <code>root</code> або <code>all</code>), <code>date_from</code>, <code>date_to</code>. Повертає бінарний <code>.zip</code> з <code>Content-Disposition: attachment</code>.</p>
                            <table class="docs-table">
                                <thead><tr><th>Код</th><th>Причина</th></tr></thead>
                                <tbody>
                                    <tr><td>404</td><td>Жодного файлу не знайдено за фільтрами</td></tr>
                                    <tr><td>413</td><td>Архів обмежений 500 файлами — звузьте фільтри</td></tr>
                                    <tr><td>503</td><td>На сервері немає розширення PHP ZipArchive</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="docs-endpoint is-delete has-body" id="files-delete">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-delete">DELETE</span>
                            <span class="docs-endpoint-path">/api/v1/files/{id}</span>
                            <span class="docs-endpoint-title">Видалити файл</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Видаляє як запис у БД, так і фізичний файл (локально або через <code>deleteMessage</code> у Telegram).</p>
                        </div>
                    </article>
                </section>

                {{-- ============ FOLDERS ============ --}}
                <section class="docs-section" id="folders">
                    <h2>Папки</h2>

                    <article class="docs-endpoint is-get has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-path">/api/v1/folders</span>
                            <span class="docs-endpoint-title">Список папок</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Усі папки поточного юзера з лічильником <code>files_count</code>, кольором і полем <code>is_password_protected</code>.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint is-get">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-path">/api/v1/folders/{id}</span>
                            <span class="docs-endpoint-title">Одна папка</span>
                        </div>
                    </article>

                    <article class="docs-endpoint is-post has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-path">/api/v1/folders</span>
                            <span class="docs-endpoint-title">Створити папку</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Body JSON:</p>
<pre><code>{ "name": "Archive", "color": "indigo", "password": "s3cret!" }</code></pre>
                            <table class="docs-table">
                                <thead><tr><th>Поле</th><th>Тип</th><th>Опис</th></tr></thead>
                                <tbody>
                                    <tr><td>name</td><td>string, required</td><td>Унікальна в межах акаунта, до 100 символів.</td></tr>
                                    <tr><td>color</td><td>string, optional</td><td>Один із ключів палітри: slate, red, orange, amber, green, teal, blue, indigo, purple, pink.</td></tr>
                                    <tr><td>password</td><td>string, optional</td><td>Мінімум 4 символи. Захищає файли всередині AES-GCM-шифруванням. <strong>Можна встановити лише під час створення</strong> — додати пароль до вже існуючої незахищеної папки через API не можна.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="docs-endpoint is-patch has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-patch">PATCH</span>
                            <span class="docs-endpoint-path">/api/v1/folders/{id}</span>
                            <span class="docs-endpoint-title">Перейменувати / змінити колір</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Body JSON: <code>{ "name": "New name", "color": "teal" }</code></p>
                        </div>
                    </article>

                    <article class="docs-endpoint is-delete has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-delete">DELETE</span>
                            <span class="docs-endpoint-path">/api/v1/folders/{id}</span>
                            <span class="docs-endpoint-title">Видалити папку</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Видаляє папку <strong>з усіма файлами всередині</strong>. Telegram-повідомлення також видаляються через бота.</p>
                        </div>
                    </article>

                    <h3 id="folders-password">Пароль папки</h3>
                    <p>Пароль папки — це захист для веб-кабінету (файли всередині зашифровані AES-GCM). Для власника, автентифікованого API-токеном, вміст папки доступний завжди незалежно від пароля — пароль не блокує API-доступ, лише веб-перегляд без токена.</p>

                    <article class="docs-endpoint is-patch has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-patch">PATCH</span>
                            <span class="docs-endpoint-path">/api/v1/folders/{id}/password</span>
                            <span class="docs-endpoint-title">Змінити пароль</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Лише для вже захищеної папки (див. вище — встановити перший пароль можна тільки при створенні). Body JSON:</p>
<pre><code>{ "current_password": "s3cret!", "password": "n3wSecret!" }</code></pre>
                            <p><code>422</code>, якщо папка не захищена або <code>current_password</code> невірний.</p>
                        </div>
                    </article>
                    <article class="docs-endpoint is-delete has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-delete">DELETE</span>
                            <span class="docs-endpoint-path">/api/v1/folders/{id}/password</span>
                            <span class="docs-endpoint-title">Зняти пароль</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Body JSON: <code>{ "current_password": "s3cret!" }</code>. <code>422</code>, якщо папка не захищена або пароль невірний.</p>
                        </div>
                    </article>
                </section>

                {{-- ============ TELEGRAM GROUPS ============ --}}
                <section class="docs-section" id="telegram-groups">
                    <h2>Telegram-групи</h2>
                    <article class="docs-endpoint is-get has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-path">/api/v1/telegram-groups</span>
                            <span class="docs-endpoint-title">Список власних груп сховища</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Повертає ваші власні Telegram-групи сховища — саме звідси беруться валідні значення <code>telegram_storage_group_id</code> для <a href="#files-upload">POST /files</a>. Якщо у вас немає власних груп, список порожній — файли автоматично підуть у системне Telegram-сховище (для не-адмінів) або локально (для адмінів), і <code>telegram_storage_group_id</code> передавати не потрібно.</p>
<pre><code>[
  {
    "id": 2,
    "title": "My storage",
    "is_default": true,
    "bot": { "id": 1, "name": "My bot", "username": "my_storage_bot" }
  }
]</code></pre>
                        </div>
                    </article>
                </section>

                {{-- ============ SHARES ============ --}}
                <section class="docs-section" id="shares">
                    <h2>Шеринг</h2>
                    <p>Файли та папки можна публікувати з обмеженнями: кількість переглядів і термін дії. Для кожної дії — окремі ендпоінти для файлу і для папки.</p>

                    <h3>Увімкнути шеринг</h3>
                    <article class="docs-endpoint is-post">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-path">/api/v1/files/{id}/share</span>
                            <span class="docs-endpoint-title">Опублікувати файл</span>
                        </div>
                    </article>
                    <article class="docs-endpoint is-post has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-path">/api/v1/folders/{id}/share</span>
                            <span class="docs-endpoint-title">Опублікувати папку</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Повертає ресурс із полем <code>share.url</code> — публічне посилання.</p>
                        </div>
                    </article>

                    <h3>Налаштувати ліміти</h3>
                    <article class="docs-endpoint is-patch">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-patch">PATCH</span>
                            <span class="docs-endpoint-path">/api/v1/files/{id}/share</span>
                            <span class="docs-endpoint-title">Ліміти файлу</span>
                        </div>
                    </article>
                    <article class="docs-endpoint is-patch has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-patch">PATCH</span>
                            <span class="docs-endpoint-path">/api/v1/folders/{id}/share</span>
                            <span class="docs-endpoint-title">Ліміти папки</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Body JSON (обидва поля nullable):</p>
<pre><code>{
  "share_max_views": 50,
  "share_expires_at": "2026-12-31T23:59:00Z"
}</code></pre>
                            <p>Передайте <code>null</code>, щоб зняти конкретний ліміт.</p>
                        </div>
                    </article>

                    <h3>Вимкнути шеринг</h3>
                    <article class="docs-endpoint is-delete">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-delete">DELETE</span>
                            <span class="docs-endpoint-path">/api/v1/files/{id}/share</span>
                            <span class="docs-endpoint-title">Закрити шер файлу</span>
                        </div>
                    </article>
                    <article class="docs-endpoint is-delete has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-delete">DELETE</span>
                            <span class="docs-endpoint-path">/api/v1/folders/{id}/share</span>
                            <span class="docs-endpoint-title">Закрити шер папки</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Видаляє публічне посилання — наступні відвідування дадуть 404.</p>
                        </div>
                    </article>
                </section>

                {{-- ============ STATS ============ --}}
                <section class="docs-section" id="stats">
                    <h2>Статистика</h2>
                    <article class="docs-endpoint is-get has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-path">/api/v1/stats</span>
                            <span class="docs-endpoint-title">Зведена статистика акаунта</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>JSON-еквівалент кабінетної сторінки «Статистика»: лічильники, топ-5 найбільших і найновіших файлів, топ-8 папок за кількістю файлів, розбивка сховища за категоріями.</p>
<pre><code>{
  "total": 128,
  "storage_bytes": 1704331264,
  "storage_human": "1.6 GB",
  "folders": 9,
  "telegram": 120,
  "local": 8,
  "root": 15,
  "shared_files": 3,
  "largest_files": [ /* ManagedFile, як у GET /files/{id} */ ],
  "recent_files": [ /* ManagedFile, як у GET /files/{id} */ ],
  "folders_by_files": [
    { "id": 5, "name": "Archive", "files_count": 40 }
  ],
  "storage_by_category": [
    { "key": "image", "label": "Зображення", "color": "#16a34a", "bytes": 900000000 }
  ]
}</code></pre>
                        </div>
                    </article>
                </section>

                {{-- ============ EXAMPLES ============ --}}
                <section class="docs-section" id="examples">
                    <h2>Приклади</h2>

                    <h3>Список файлів</h3>
                    <div class="docs-example">
                        <div class="docs-example-head">
                            <span class="docs-example-lang">curl</span>
                            <span>GET /api/v1/files</span>
                        </div>
<pre><code>curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     "{{ url('/api/v1/files') }}?per_page=10&storage_driver=telegram"</code></pre>
                    </div>

                    <h3>Upload файлу</h3>
                    <div class="docs-example">
                        <div class="docs-example-head">
                            <span class="docs-example-lang">curl</span>
                            <span>POST /api/v1/files</span>
                        </div>
<pre><code>curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     -F "file=@/path/to/document.pdf" \
     -F "folder_id=12" \
     "{{ url('/api/v1/files') }}"</code></pre>
                    </div>

                    <h3>Створити шер з лімітом 100 переглядів</h3>
                    <div class="docs-example">
                        <div class="docs-example-head">
                            <span class="docs-example-lang">curl</span>
                            <span>POST + PATCH /api/v1/files/{id}/share</span>
                        </div>
<pre><code>curl -X POST -H "Authorization: Bearer YOUR_TOKEN" \
     "{{ url('/api/v1/files/123/share') }}"

curl -X PATCH -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"share_max_views": 100}' \
     "{{ url('/api/v1/files/123/share') }}"</code></pre>
                    </div>

                    <h3>Масове видалення + теги</h3>
                    <div class="docs-example">
                        <div class="docs-example-head">
                            <span class="docs-example-lang">curl</span>
                            <span>POST /files/bulk-delete, PATCH /files/{id}/tags</span>
                        </div>
<pre><code>curl -X POST -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"ids": [101, 102, 103]}' \
     "{{ url('/api/v1/files/bulk-delete') }}"

curl -X PATCH -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"tags": ["work", "invoices"]}' \
     "{{ url('/api/v1/files/123/tags') }}"</code></pre>
                    </div>

                    <h3>PHP (Guzzle)</h3>
                    <div class="docs-example">
                        <div class="docs-example-head">
                            <span class="docs-example-lang">PHP</span>
                            <span>Multipart upload через Guzzle</span>
                        </div>
<pre><code>$client = new \GuzzleHttp\Client([
    'base_uri' =&gt; '{{ url('/api/v1/') }}',
    'headers' =&gt; [
        'Authorization' =&gt; 'Bearer ' . getenv('FILEPROXY_TOKEN'),
        'Accept' =&gt; 'application/json',
    ],
]);

$response = $client-&gt;post('files', [
    'multipart' =&gt; [
        ['name' =&gt; 'file', 'contents' =&gt; fopen('/tmp/report.csv', 'r')],
        ['name' =&gt; 'folder_id', 'contents' =&gt; '5'],
    ],
]);

$data = json_decode((string) $response-&gt;getBody(), true);</code></pre>
                    </div>

                    <h3>PHP (нативний cURL)</h3>
                    <div class="docs-example">
                        <div class="docs-example-head">
                            <span class="docs-example-lang">PHP</span>
                            <span>Без зовнішніх залежностей</span>
                        </div>
<pre><code>$ch = curl_init('{{ url('/api/v1/files') }}');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER =&gt; [
        'Authorization: Bearer ' . getenv('FILEPROXY_TOKEN'),
        'Accept: application/json',
    ],
    CURLOPT_RETURNTRANSFER =&gt; true,
]);
$json = curl_exec($ch);
$files = json_decode($json, true)['data'] ?? [];</code></pre>
                    </div>
                </section>
            </main>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Highlight active TOC link based on visible section
    (() => {
        const links = document.querySelectorAll('.docs-aside a[href^="#"]');
        if (!links.length) return;

        const linkMap = new Map();
        const targets = [];

        links.forEach((link) => {
            const id = link.getAttribute('href').slice(1);
            const target = document.getElementById(id);
            if (target) {
                linkMap.set(id, link);
                targets.push(target);
            }
        });

        if (!targets.length) return;

        const setActive = (id) => {
            links.forEach((l) => l.classList.remove('is-active'));
            const active = linkMap.get(id);
            if (active) active.classList.add('is-active');
        };

        const observer = new IntersectionObserver((entries) => {
            const visible = entries
                .filter((e) => e.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
            if (visible) setActive(visible.target.id);
        }, {
            rootMargin: '-20% 0px -70% 0px',
            threshold: 0,
        });

        targets.forEach((t) => observer.observe(t));
    })();
</script>
@endpush
