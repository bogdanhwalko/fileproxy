@extends('layouts.site')

@section('title', 'API-документація — FileProxy')
@section('description', 'Документація REST API FileProxy: автентифікація, ендпоінти для файлів, папок і публічних посилань. Приклади на curl і PHP.')

@push('head')
    <style>
        .docs-page {
            --docs-bg: #ffffff;
            --docs-fg: #0f172a;
            --docs-muted: #64748b;
            --docs-border: #e2e8f0;
            --docs-soft: #f8fafc;
            --docs-accent: #4f46e5;
            --docs-code-bg: #f1f5f9;
            --docs-code-fg: #0f172a;
            --docs-code-border: #d8dee9;
            --docs-pre-bg: #0f172a;
            --docs-pre-fg: #e2e8f0;

            background: var(--docs-bg);
            color: var(--docs-fg);
            min-height: 100vh;
            padding: 0 16px 64px;
        }

        .docs-wrap {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
            gap: 40px;
            padding-top: 24px;
        }

        .docs-aside {
            position: sticky; top: 16px; align-self: start;
            max-height: calc(100vh - 32px); overflow-y: auto;
            padding: 18px 16px;
            background: var(--docs-soft);
            border: 1px solid var(--docs-border);
            border-radius: 14px;
            font-size: 14px;
        }
        .docs-aside-title {
            display: block; font-size: 11px; letter-spacing: 0.8px;
            text-transform: uppercase; color: var(--docs-muted);
            margin-bottom: 10px;
        }
        .docs-aside ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 2px; }
        .docs-aside a {
            display: block; padding: 6px 10px; border-radius: 8px;
            color: var(--docs-fg); text-decoration: none; transition: background 0.12s;
        }
        .docs-aside a:hover { background: rgba(79, 70, 229, 0.08); color: var(--docs-accent); }
        .docs-aside .docs-aside-sub {
            padding-left: 20px; font-size: 13px; color: var(--docs-muted);
        }
        .docs-aside .docs-aside-sub a { padding: 4px 10px; }

        .docs-main { min-width: 0; }

        .docs-hero { margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--docs-border); }
        .docs-hero h1 { font-size: 36px; margin: 0 0 8px; letter-spacing: -0.4px; }
        .docs-hero .docs-hero-lead { font-size: 16px; color: var(--docs-muted); line-height: 1.6; margin: 0 0 16px; max-width: 70ch; }
        .docs-hero-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .docs-hero-meta .docs-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; background: var(--docs-soft);
            border: 1px solid var(--docs-border); border-radius: 999px;
            font-size: 12px; color: var(--docs-muted);
        }
        .docs-hero-meta .docs-pill strong { color: var(--docs-fg); font-weight: 600; }

        .docs-section { scroll-margin-top: 20px; padding: 8px 0 4px; }
        .docs-section + .docs-section { margin-top: 36px; border-top: 1px solid var(--docs-border); padding-top: 32px; }
        .docs-section h2 { font-size: 24px; margin: 0 0 12px; letter-spacing: -0.2px; }
        .docs-section h3 { font-size: 17px; margin: 22px 0 8px; color: var(--docs-fg); }
        .docs-section p { line-height: 1.65; color: var(--docs-fg); margin: 8px 0 12px; max-width: 72ch; }

        .docs-endpoint {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 8px 14px; background: var(--docs-soft);
            border: 1px solid var(--docs-border); border-radius: 10px;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 14px; margin: 6px 0;
            color: var(--docs-fg);
        }
        .docs-endpoint .docs-method {
            display: inline-flex; align-items: center;
            padding: 2px 8px; border-radius: 5px;
            font-weight: 700; font-size: 10.5px; letter-spacing: 0.5px;
            text-transform: uppercase; color: white; font-family: inherit;
            min-width: 52px; justify-content: center;
        }
        .docs-method.m-get { background: #0e7490; }
        .docs-method.m-post { background: #16a34a; }
        .docs-method.m-patch { background: #ca8a04; }
        .docs-method.m-delete { background: #dc2626; }

        .docs-section code {
            background: var(--docs-code-bg); color: var(--docs-code-fg);
            border: 1px solid var(--docs-code-border);
            padding: 1.5px 6px; border-radius: 4px;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 0.88em;
        }
        .docs-section pre {
            background: var(--docs-pre-bg); color: var(--docs-pre-fg);
            padding: 14px 16px; border-radius: 10px;
            overflow-x: auto; font-size: 13px; line-height: 1.55;
            margin: 10px 0 18px;
            border: 1px solid #1e293b;
        }
        .docs-section pre code {
            background: transparent; color: inherit; border: 0; padding: 0;
            font-size: inherit;
        }

        .docs-table {
            width: 100%; border-collapse: collapse;
            margin: 10px 0 18px; font-size: 14px;
            border: 1px solid var(--docs-border); border-radius: 10px;
            overflow: hidden;
        }
        .docs-table thead { background: var(--docs-soft); }
        .docs-table th {
            text-align: left; padding: 10px 14px;
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--docs-muted); border-bottom: 1px solid var(--docs-border);
        }
        .docs-table td {
            padding: 10px 14px; border-bottom: 1px solid var(--docs-border);
            vertical-align: top; line-height: 1.5;
        }
        .docs-table tbody tr:last-child td { border-bottom: 0; }
        .docs-table td:first-child { font-family: ui-monospace, Consolas, monospace; font-size: 13px; }

        .docs-callout {
            background: rgba(79, 70, 229, 0.08); border-left: 3px solid var(--docs-accent);
            padding: 12px 16px; border-radius: 8px; margin: 14px 0;
            font-size: 14px; line-height: 1.55;
        }
        .docs-callout strong { color: var(--docs-accent); }

        .docs-section ul.docs-list { padding-left: 22px; margin: 8px 0 14px; line-height: 1.6; }
        .docs-section ul.docs-list li { margin: 4px 0; }

        .docs-cta {
            display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
            margin: 16px 0 24px;
        }

        @media (max-width: 900px) {
            .docs-wrap { grid-template-columns: 1fr; gap: 24px; }
            .docs-aside {
                position: static; max-height: none;
                display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            }
            .docs-aside ul { grid-template-columns: 1fr; }
            .docs-hero h1 { font-size: 28px; }
        }
    </style>
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
                    <li class="docs-aside-sub"><a href="#files-list">→ Список</a></li>
                    <li class="docs-aside-sub"><a href="#files-show">→ Метадані</a></li>
                    <li class="docs-aside-sub"><a href="#files-content">→ Скачати</a></li>
                    <li class="docs-aside-sub"><a href="#files-upload">→ Upload</a></li>
                    <li class="docs-aside-sub"><a href="#files-delete">→ Видалити</a></li>
                    <li><a href="#folders">Папки</a></li>
                    <li><a href="#shares">Шеринг</a></li>
                    <li><a href="#examples">Приклади</a></li>
                </ul>
            </aside>

            <main class="docs-main">
                <div class="docs-hero">
                    <h1>API FileProxy</h1>
                    <p class="docs-hero-lead">REST API для керування файлами, папками та публічними посиланнями. Автентифікація — bearer-токени (Laravel Sanctum). Усі відповіді — JSON. Помилки — англійською.</p>
                    <div class="docs-hero-meta">
                        <span class="docs-pill"><strong>Base URL:</strong> <code>{{ url('/api/v1') }}</code></span>
                        <span class="docs-pill"><strong>Версія:</strong> v1</span>
                        <span class="docs-pill"><strong>Формат:</strong> JSON</span>
                    </div>
                </div>

                <section class="docs-section" id="auth">
                    <h2>Автентифікація</h2>
                    <p>Усі запити до <code>/api/v1/*</code> вимагають заголовок <code>Authorization: Bearer &lt;token&gt;</code>. Токен створюється в кабінеті:</p>
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
                        <strong>Важливо.</strong> Токен показується <strong>один раз</strong>. Збережіть його в менеджері паролів або зашифрованому сховищі. Відкликати можна будь-коли на сторінці токенів.
                    </div>
                </section>

                <section class="docs-section" id="errors">
                    <h2>Формат помилок</h2>
                    <p>Будь-яка помилка повертається в єдиному форматі:</p>
<pre><code>{
  "message": "Human-readable description in English",
  "errors": {
    "field_name": ["Specific validation error"]
  }
}</code></pre>
                    <table class="docs-table">
                        <thead><tr><th>Код</th><th>Коли</th></tr></thead>
                        <tbody>
                            <tr><td>401</td><td>Token відсутній, прострочений або відкликаний</td></tr>
                            <tr><td>403</td><td>Акаунт заблоковано адміністратором / перевищено системний ліміт</td></tr>
                            <tr><td>404</td><td>Ресурс не існує або не належить вам</td></tr>
                            <tr><td>409</td><td>Конфлікт (інше завантаження вже триває)</td></tr>
                            <tr><td>422</td><td>Помилка валідації — деталі у полі <code>errors</code></td></tr>
                            <tr><td>429</td><td>Rate limit. Перевірте заголовки <code>X-RateLimit-*</code></td></tr>
                            <tr><td>500</td><td>Несподівана помилка сервера</td></tr>
                        </tbody>
                    </table>
                </section>

                <section class="docs-section" id="rate-limits">
                    <h2>Ліміти</h2>
                    <table class="docs-table">
                        <thead><tr><th>Ендпоінт</th><th>Ліміт</th></tr></thead>
                        <tbody>
                            <tr><td>Загальний</td><td>60 запитів / хвилину на токен</td></tr>
                            <tr><td><code>POST /files</code> (upload)</td><td>30 запитів / хвилину на токен</td></tr>
                        </tbody>
                    </table>
                </section>

                <section class="docs-section" id="user">
                    <h2>Поточний користувач</h2>
                    <div class="docs-endpoint">
                        <span class="docs-method m-get">GET</span> /api/v1/user
                    </div>
<pre><code>{
  "id": 1,
  "name": "Bohdan",
  "phone": "+380...",
  "is_admin": false
}</code></pre>
                </section>

                <section class="docs-section" id="files">
                    <h2>Файли</h2>

                    <h3 id="files-list">Список файлів</h3>
                    <div class="docs-endpoint"><span class="docs-method m-get">GET</span> /api/v1/files</div>
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

                    <h3 id="files-show">Метадані одного файлу</h3>
                    <div class="docs-endpoint"><span class="docs-method m-get">GET</span> /api/v1/files/{id}</div>

                    <h3 id="files-content">Завантажити файл (бінарка)</h3>
                    <div class="docs-endpoint"><span class="docs-method m-get">GET</span> /api/v1/files/{id}/content</div>
                    <p>Повертає файл з оригінальним MIME та <code>Content-Disposition: attachment</code>. Безпечно для будь-яких типів — заголовок <code>X-Content-Type-Options: nosniff</code> блокує MIME-sniffing.</p>

                    <h3 id="files-upload">Upload</h3>
                    <div class="docs-endpoint"><span class="docs-method m-post">POST</span> /api/v1/files</div>
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

                    <h3 id="files-delete">Видалити файл</h3>
                    <div class="docs-endpoint"><span class="docs-method m-delete">DELETE</span> /api/v1/files/{id}</div>
                    <p>Видаляє як запис у БД, так і фізичний файл (локально або через <code>deleteMessage</code> у Telegram).</p>
                </section>

                <section class="docs-section" id="folders">
                    <h2>Папки</h2>
                    <div class="docs-endpoint"><span class="docs-method m-get">GET</span> /api/v1/folders</div>
                    <div class="docs-endpoint"><span class="docs-method m-post">POST</span> /api/v1/folders</div>
                    <div class="docs-endpoint"><span class="docs-method m-get">GET</span> /api/v1/folders/{id}</div>
                    <div class="docs-endpoint"><span class="docs-method m-patch">PATCH</span> /api/v1/folders/{id}</div>
                    <div class="docs-endpoint"><span class="docs-method m-delete">DELETE</span> /api/v1/folders/{id}</div>

                    <h3>Створення / перейменування</h3>
                    <p>Body JSON:</p>
<pre><code>{ "name": "Archive" }</code></pre>
                    <p>Назва унікальна в межах акаунта, до 100 символів.</p>

                    <h3>Видалення</h3>
                    <p><code>DELETE /folders/{id}</code> видаляє папку <strong>з усіма файлами всередині</strong>. Telegram-повідомлення також видаляються через бота.</p>
                </section>

                <section class="docs-section" id="shares">
                    <h2>Шеринг</h2>
                    <p>Файли та папки можна публікувати з обмеженнями: кількість переглядів і термін дії.</p>

                    <h3>Увімкнути шеринг</h3>
                    <div class="docs-endpoint"><span class="docs-method m-post">POST</span> /api/v1/files/{id}/share</div>
                    <div class="docs-endpoint"><span class="docs-method m-post">POST</span> /api/v1/folders/{id}/share</div>
                    <p>Повертає ресурс з полем <code>share.url</code> — публічне посилання.</p>

                    <h3>Налаштувати ліміти</h3>
                    <div class="docs-endpoint"><span class="docs-method m-patch">PATCH</span> /api/v1/files/{id}/share</div>
                    <div class="docs-endpoint"><span class="docs-method m-patch">PATCH</span> /api/v1/folders/{id}/share</div>
                    <p>Body JSON (обидва поля nullable):</p>
<pre><code>{
  "share_max_views": 50,
  "share_expires_at": "2026-12-31T23:59:00Z"
}</code></pre>
                    <p>Передайте <code>null</code> в полі, щоб зняти ліміт.</p>

                    <h3>Вимкнути шеринг</h3>
                    <div class="docs-endpoint"><span class="docs-method m-delete">DELETE</span> /api/v1/files/{id}/share</div>
                    <div class="docs-endpoint"><span class="docs-method m-delete">DELETE</span> /api/v1/folders/{id}/share</div>
                    <p>Видаляє публічне посилання — наступні відвідування дадуть 404.</p>
                </section>

                <section class="docs-section" id="examples">
                    <h2>Приклади</h2>

                    <h3>Список файлів (curl)</h3>
<pre><code>curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     "{{ url('/api/v1/files') }}?per_page=10&storage_driver=telegram"</code></pre>

                    <h3>Upload файлу (curl)</h3>
<pre><code>curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     -F "file=@/path/to/document.pdf" \
     -F "folder_id=12" \
     "{{ url('/api/v1/files') }}"</code></pre>

                    <h3>Створити шер з лімітом 100 переглядів</h3>
<pre><code>curl -X POST -H "Authorization: Bearer YOUR_TOKEN" \
     "{{ url('/api/v1/files/123/share') }}"

curl -X PATCH -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"share_max_views": 100}' \
     "{{ url('/api/v1/files/123/share') }}"</code></pre>

                    <h3>PHP (Guzzle)</h3>
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

                    <h3>PHP (нативний cURL)</h3>
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
                </section>
            </main>
        </div>
    </div>
@endsection
