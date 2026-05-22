@extends('layouts.site')

@section('title', 'API-документація — FileProxy')
@section('description', 'Документація REST API FileProxy: автентифікація, ендпоінти для файлів, папок і публічних посилань. Приклади на curl і PHP.')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/docs.css') }}">
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
                    <article class="docs-endpoint-card is-get">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-card-path">/api/v1/user</span>
                            <span class="docs-endpoint-card-title">Дані поточного юзера</span>
                        </header>
                        <div class="docs-endpoint-card-body">
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

                <section class="docs-section" id="files">
                    <h2>Файли</h2>

                    <article class="docs-endpoint-card is-get" id="files-list">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-card-path">/api/v1/files</span>
                            <span class="docs-endpoint-card-title">Список файлів</span>
                        </header>
                        <div class="docs-endpoint-card-body">
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

                    <article class="docs-endpoint-card is-get" id="files-show">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-card-path">/api/v1/files/{id}</span>
                            <span class="docs-endpoint-card-title">Метадані одного файлу</span>
                        </header>
                        <div class="docs-endpoint-card-body">
                            <p>Повертає одну сутність <code>ManagedFile</code> з повним набором полів і <code>share</code>-блоком, якщо ввімкнено публічний доступ.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint-card is-get" id="files-content">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-card-path">/api/v1/files/{id}/content</span>
                            <span class="docs-endpoint-card-title">Скачати файл</span>
                        </header>
                        <div class="docs-endpoint-card-body">
                            <p>Повертає файл з оригінальним MIME та <code>Content-Disposition: attachment</code>. Безпечно для будь-яких типів — заголовок <code>X-Content-Type-Options: nosniff</code> блокує MIME-sniffing.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint-card is-post" id="files-upload">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-card-path">/api/v1/files</span>
                            <span class="docs-endpoint-card-title">Upload</span>
                        </header>
                        <div class="docs-endpoint-card-body">
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

                    <article class="docs-endpoint-card is-delete" id="files-delete">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-delete">DELETE</span>
                            <span class="docs-endpoint-card-path">/api/v1/files/{id}</span>
                            <span class="docs-endpoint-card-title">Видалити файл</span>
                        </header>
                        <div class="docs-endpoint-card-body">
                            <p>Видаляє як запис у БД, так і фізичний файл (локально або через <code>deleteMessage</code> у Telegram).</p>
                        </div>
                    </article>
                </section>

                <section class="docs-section" id="folders">
                    <h2>Папки</h2>

                    <article class="docs-endpoint-card is-get">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-card-path">/api/v1/folders</span>
                            <span class="docs-endpoint-card-title">Список папок</span>
                        </header>
                        <div class="docs-endpoint-card-body">
                            <p>Усі папки поточного юзера з лічильником <code>files_count</code>.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint-card is-get">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-get">GET</span>
                            <span class="docs-endpoint-card-path">/api/v1/folders/{id}</span>
                            <span class="docs-endpoint-card-title">Одна папка</span>
                        </header>
                    </article>

                    <article class="docs-endpoint-card is-post">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-card-path">/api/v1/folders</span>
                            <span class="docs-endpoint-card-title">Створити папку</span>
                        </header>
                        <div class="docs-endpoint-card-body">
                            <p>Body JSON:</p>
<pre><code>{ "name": "Archive" }</code></pre>
                            <p>Назва унікальна в межах акаунта, до 100 символів.</p>
                        </div>
                    </article>

                    <article class="docs-endpoint-card is-patch">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-patch">PATCH</span>
                            <span class="docs-endpoint-card-path">/api/v1/folders/{id}</span>
                            <span class="docs-endpoint-card-title">Перейменувати</span>
                        </header>
                        <div class="docs-endpoint-card-body">
                            <p>Body JSON: <code>{ "name": "New name" }</code></p>
                        </div>
                    </article>

                    <article class="docs-endpoint-card is-delete">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-delete">DELETE</span>
                            <span class="docs-endpoint-card-path">/api/v1/folders/{id}</span>
                            <span class="docs-endpoint-card-title">Видалити папку</span>
                        </header>
                        <div class="docs-endpoint-card-body">
                            <p>Видаляє папку <strong>з усіма файлами всередині</strong>. Telegram-повідомлення також видаляються через бота.</p>
                        </div>
                    </article>
                </section>

                <section class="docs-section" id="shares">
                    <h2>Шеринг</h2>
                    <p>Файли та папки можна публікувати з обмеженнями: кількість переглядів і термін дії. Для кожної дії — окремі ендпоінти для файлу і для папки.</p>

                    <h3>Увімкнути шеринг</h3>
                    <article class="docs-endpoint-card is-post">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-card-path">/api/v1/files/{id}/share</span>
                            <span class="docs-endpoint-card-title">Опублікувати файл</span>
                        </header>
                    </article>
                    <article class="docs-endpoint-card is-post">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-post">POST</span>
                            <span class="docs-endpoint-card-path">/api/v1/folders/{id}/share</span>
                            <span class="docs-endpoint-card-title">Опублікувати папку</span>
                        </header>
                        <div class="docs-endpoint-card-body">
                            <p>Повертає ресурс із полем <code>share.url</code> — публічне посилання.</p>
                        </div>
                    </article>

                    <h3>Налаштувати ліміти</h3>
                    <article class="docs-endpoint-card is-patch">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-patch">PATCH</span>
                            <span class="docs-endpoint-card-path">/api/v1/files/{id}/share</span>
                            <span class="docs-endpoint-card-title">Ліміти файлу</span>
                        </header>
                    </article>
                    <article class="docs-endpoint-card is-patch">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-patch">PATCH</span>
                            <span class="docs-endpoint-card-path">/api/v1/folders/{id}/share</span>
                            <span class="docs-endpoint-card-title">Ліміти папки</span>
                        </header>
                        <div class="docs-endpoint-card-body">
                            <p>Body JSON (обидва поля nullable):</p>
<pre><code>{
  "share_max_views": 50,
  "share_expires_at": "2026-12-31T23:59:00Z"
}</code></pre>
                            <p>Передайте <code>null</code>, щоб зняти конкретний ліміт.</p>
                        </div>
                    </article>

                    <h3>Вимкнути шеринг</h3>
                    <article class="docs-endpoint-card is-delete">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-delete">DELETE</span>
                            <span class="docs-endpoint-card-path">/api/v1/files/{id}/share</span>
                            <span class="docs-endpoint-card-title">Закрити шер файлу</span>
                        </header>
                    </article>
                    <article class="docs-endpoint-card is-delete">
                        <header class="docs-endpoint-card-head">
                            <span class="docs-method m-delete">DELETE</span>
                            <span class="docs-endpoint-card-path">/api/v1/folders/{id}/share</span>
                            <span class="docs-endpoint-card-title">Закрити шер папки</span>
                        </header>
                        <div class="docs-endpoint-card-body">
                            <p>Видаляє публічне посилання — наступні відвідування дадуть 404.</p>
                        </div>
                    </article>
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
