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
                    <li><a href="#errors">Формат помилок</a></li>
                    <li><a href="#rate-limits">Ліміти</a></li>
                    <li><a href="#user">User</a></li>
                    <li><a href="#files">Файли</a></li>
                    <li class="docs-aside-sub"><a href="#files-list">Список</a></li>
                    <li class="docs-aside-sub"><a href="#files-show">Метадані</a></li>
                    <li class="docs-aside-sub"><a href="#files-content">Скачати</a></li>
                    <li class="docs-aside-sub"><a href="#files-upload">Upload</a></li>
                    <li class="docs-aside-sub"><a href="#files-delete">Видалити</a></li>
                    <li><a href="#folders">Папки</a></li>
                    <li><a href="#shares">Шеринг</a></li>
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
                        <div class="docs-status-item"><span class="docs-status-code c-401">401</span><div>Token відсутній, прострочений або відкликаний</div></div>
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
                            <tr><td>Загальний</td><td>60 запитів / хвилину на токен</td></tr>
                            <tr><td>POST /files <em>(upload)</em></td><td>30 запитів / хвилину на токен</td></tr>
                        </tbody>
                    </table>
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
                            <p>Повертає одну сутність <code>ManagedFile</code> з повним набором полів і <code>share</code>-блоком, якщо ввімкнено публічний доступ.</p>
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
                            <p>Усі папки поточного юзера з лічильником <code>files_count</code>.</p>
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
<pre><code>{ "name": "Archive" }</code></pre>
                            <p>Назва унікальна в межах акаунта, до 100 символів.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint is-patch has-body">
                        <div class="docs-endpoint-head">
                            <span class="docs-method m-patch">PATCH</span>
                            <span class="docs-endpoint-path">/api/v1/folders/{id}</span>
                            <span class="docs-endpoint-title">Перейменувати</span>
                        </div>
                        <div class="docs-endpoint-body">
                            <p>Body JSON: <code>{ "name": "New name" }</code></p>
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
