@extends('layouts.site')

@section('title', 'API-документація — FileProxy')
@section('description', 'Документація REST API FileProxy: автентифікація, ендпоінти для файлів, папок і публічних посилань. Приклади на curl і PHP.')

@push('head')
    <style>
        .docs-shell { max-width: 980px; margin: 0 auto; padding: 24px 16px; }
        .docs-shell h1 { font-size: 32px; margin: 0 0 8px; }
        .docs-shell h2 { font-size: 22px; margin: 32px 0 12px; padding-top: 12px; border-top: 1px solid rgba(120,120,140,0.18); }
        .docs-shell h3 { font-size: 17px; margin: 20px 0 8px; }
        .docs-shell p { line-height: 1.55; }
        .docs-shell code, .docs-shell pre { font-family: 'SFMono-Regular', Consolas, Monaco, monospace; }
        .docs-shell pre {
            background: #0f172a; color: #e2e8f0; padding: 14px 16px; border-radius: 10px;
            overflow-x: auto; font-size: 13px; line-height: 1.5; margin: 8px 0 16px;
        }
        .docs-shell code:not(pre code) {
            background: rgba(120,120,140,0.14); padding: 2px 6px; border-radius: 4px; font-size: 0.92em;
        }
        .docs-shell .endpoint {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(120,120,140,0.10); border-radius: 8px;
            padding: 6px 12px; font-family: monospace; font-size: 14px;
            margin: 4px 0;
        }
        .docs-shell .method {
            display: inline-block; padding: 2px 8px; border-radius: 4px;
            font-weight: 700; font-size: 11px; letter-spacing: 0.4px;
            text-transform: uppercase; color: white;
        }
        .method-get { background: #0e7490; }
        .method-post { background: #16a34a; }
        .method-patch { background: #ca8a04; }
        .method-delete { background: #dc2626; }
        .docs-shell table { width: 100%; border-collapse: collapse; margin: 8px 0 16px; }
        .docs-shell th, .docs-shell td { text-align: left; padding: 8px 10px; border-bottom: 1px solid rgba(120,120,140,0.18); vertical-align: top; }
        .docs-shell th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; color: #64748b; }
        .docs-toc { background: rgba(120,120,140,0.08); padding: 16px 20px; border-radius: 12px; margin: 16px 0 24px; }
        .docs-toc ul { margin: 8px 0 0; padding-left: 18px; column-count: 2; column-gap: 24px; }
        .docs-toc li { margin: 4px 0; break-inside: avoid; }
        .docs-callout {
            background: rgba(79, 70, 229, 0.08); border-left: 3px solid #4f46e5;
            padding: 12px 16px; border-radius: 8px; margin: 12px 0;
        }
    </style>
@endpush

@section('content')
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>REST API · версія v1</p>
            </div>
        </a>

        <div class="nav-actions">
            @auth
                <a class="button secondary" href="{{ route('files.index') }}">Кабінет</a>
                <a class="button secondary" href="{{ route('api-tokens.index') }}">Мої токени</a>
            @else
                <a class="button secondary" href="{{ route('login') }}">Увійти</a>
                <a class="button" href="{{ route('register') }}">Зареєструватись</a>
            @endauth
        </div>
    </header>

    <main class="docs-shell">
        <h1>API FileProxy</h1>
        <p>REST API для керування файлами, папками та публічними посиланнями. Автентифікація — bearer-токени (Sanctum). Усі запити повертають JSON.</p>

        <div class="docs-toc">
            <strong>Зміст</strong>
            <ul>
                <li><a href="#auth">Автентифікація</a></li>
                <li><a href="#errors">Формат помилок</a></li>
                <li><a href="#rate-limits">Ліміти</a></li>
                <li><a href="#user">GET /user</a></li>
                <li><a href="#files">Файли</a></li>
                <li><a href="#folders">Папки</a></li>
                <li><a href="#shares">Шеринг</a></li>
                <li><a href="#examples">Приклади</a></li>
            </ul>
        </div>

        <h2 id="auth">Автентифікація</h2>
        <p>Усі запити до <code>/api/v1/*</code> вимагають заголовок <code>Authorization: Bearer &lt;токен&gt;</code>. Токен створюється у вашому кабінеті:</p>
        @auth
            <p><a class="button" href="{{ route('api-tokens.index') }}">Створити API-токен</a></p>
        @else
            <p><a class="button" href="{{ route('login') }}">Увійти</a>, далі — <strong>Налаштування → API-токени</strong>.</p>
        @endauth

        <div class="docs-callout">
            Токен показується <strong>один раз</strong> після створення. Збережіть його у менеджері паролів або зашифрованому сховищі. Відкликати можна будь-коли на сторінці токенів.
        </div>

        <h2 id="errors">Формат помилок</h2>
        <p>Помилки повертаються у форматі:</p>
<pre>{
  "message": "Опис помилки українською",
  "errors": {
    "field_name": ["Конкретна валідаційна помилка"]
  }
}</pre>
        <p>Коди: <code>401</code> (немає або невірний токен), <code>403</code> (акаунт заблоковано / перевищено системний ліміт), <code>404</code> (ресурс не належить вам або не існує), <code>409</code> (паралельне завантаження), <code>422</code> (валідація), <code>429</code> (rate limit).</p>

        <h2 id="rate-limits">Ліміти</h2>
        <table>
            <thead><tr><th>Ендпоінт</th><th>Ліміт</th></tr></thead>
            <tbody>
                <tr><td>Загальний (більшість маршрутів)</td><td>60 запитів / хв</td></tr>
                <tr><td><code>POST /api/v1/files</code> (upload)</td><td>30 запитів / хв</td></tr>
            </tbody>
        </table>

        <h2 id="user">GET /user</h2>
        <div class="endpoint"><span class="method method-get">get</span> /api/v1/user</div>
        <p>Повертає мінімальні дані поточного користувача.</p>
<pre>{ "id": 1, "name": "Bohdan", "phone": "+380...", "is_admin": false }</pre>

        <h2 id="files">Файли</h2>

        <h3>Список файлів</h3>
        <div class="endpoint"><span class="method method-get">get</span> /api/v1/files</div>
        <p>Query-параметри:</p>
        <table>
            <thead><tr><th>Параметр</th><th>Тип</th><th>Опис</th></tr></thead>
            <tbody>
                <tr><td>folder_id</td><td>int / "root"</td><td>Фільтр по папці. <code>root</code> — файли в корені.</td></tr>
                <tr><td>search</td><td>string</td><td>Пошук по імені, MIME, розширенню.</td></tr>
                <tr><td>storage_driver</td><td>local / telegram</td><td>Тип сховища.</td></tr>
                <tr><td>date_from, date_to</td><td>YYYY-MM-DD</td><td>Діапазон дат створення.</td></tr>
                <tr><td>per_page</td><td>int (1–100)</td><td>Розмір сторінки. За замовчуванням 20.</td></tr>
                <tr><td>page</td><td>int</td><td>Номер сторінки.</td></tr>
            </tbody>
        </table>
        <p>Відповідь — стандартна Laravel-пагінація (<code>data</code>, <code>meta</code>, <code>links</code>).</p>

        <h3>Метадані одного файлу</h3>
        <div class="endpoint"><span class="method method-get">get</span> /api/v1/files/{id}</div>

        <h3>Завантажити файл (бінарка)</h3>
        <div class="endpoint"><span class="method method-get">get</span> /api/v1/files/{id}/content</div>
        <p>Повертає файл як <code>application/octet-stream</code> (або з оригінальним MIME) з заголовком <code>Content-Disposition: attachment</code>.</p>

        <h3>Завантажити (upload) файл</h3>
        <div class="endpoint"><span class="method method-post">post</span> /api/v1/files</div>
        <p>Multipart form-data. Поля:</p>
        <table>
            <thead><tr><th>Поле</th><th>Тип</th><th>Опис</th></tr></thead>
            <tbody>
                <tr><td><code>file</code> або <code>files[]</code></td><td>file</td><td>До 25 файлів за запит, кожен ≤ 50 MB.</td></tr>
                <tr><td>folder_id</td><td>int, optional</td><td>ID вашої папки.</td></tr>
                <tr><td>telegram_storage_group_id</td><td>int, optional</td><td>ID вашої Telegram-групи. Якщо опущено, файл піде у системне Telegram-сховище (для не-адмінів) або локально (для адмінів).</td></tr>
            </tbody>
        </table>
        <p>Відповідь <code>201 Created</code> з масивом створених файлів. Якщо завантаження в Telegram — статус буде <code>pending</code> до завершення фонового джобу.</p>

        <h3>Видалити файл</h3>
        <div class="endpoint"><span class="method method-delete">delete</span> /api/v1/files/{id}</div>

        <h2 id="folders">Папки</h2>

        <h3>Список папок</h3>
        <div class="endpoint"><span class="method method-get">get</span> /api/v1/folders</div>

        <h3>Створити папку</h3>
        <div class="endpoint"><span class="method method-post">post</span> /api/v1/folders</div>
        <p>Body JSON: <code>{ "name": "Архів" }</code> (унікальна в межах акаунта, до 100 символів).</p>

        <h3>Перейменувати</h3>
        <div class="endpoint"><span class="method method-patch">patch</span> /api/v1/folders/{id}</div>
        <p>Body JSON: <code>{ "name": "Нова назва" }</code></p>

        <h3>Видалити папку (з усіма файлами)</h3>
        <div class="endpoint"><span class="method method-delete">delete</span> /api/v1/folders/{id}</div>

        <h2 id="shares">Шеринг</h2>
        <p>Файли та папки можна публікувати з обмеженням на кількість переглядів і термін дії.</p>

        <h3>Увімкнути шеринг</h3>
        <div class="endpoint"><span class="method method-post">post</span> /api/v1/files/{id}/share</div>
        <div class="endpoint"><span class="method method-post">post</span> /api/v1/folders/{id}/share</div>
        <p>Повертає оновлений ресурс із полем <code>share.url</code> — публічне посилання.</p>

        <h3>Налаштувати ліміти</h3>
        <div class="endpoint"><span class="method method-patch">patch</span> /api/v1/files/{id}/share</div>
        <div class="endpoint"><span class="method method-patch">patch</span> /api/v1/folders/{id}/share</div>
        <p>Body JSON:</p>
<pre>{
  "share_max_views": 50,
  "share_expires_at": "2026-12-31T23:59:00Z"
}</pre>
        <p>Обидва поля nullable — передайте <code>null</code>, щоб зняти ліміт.</p>

        <h3>Вимкнути шеринг</h3>
        <div class="endpoint"><span class="method method-delete">delete</span> /api/v1/files/{id}/share</div>
        <div class="endpoint"><span class="method method-delete">delete</span> /api/v1/folders/{id}/share</div>
        <p>Видаляє публічне посилання — наступні відвідування дадуть 404.</p>

        <h2 id="examples">Приклади</h2>

        <h3>Список файлів (curl)</h3>
<pre>curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     "{{ url('/api/v1/files') }}?per_page=10&storage_driver=telegram"</pre>

        <h3>Upload (curl)</h3>
<pre>curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     -F "file=@/path/to/document.pdf" \
     -F "folder_id=12" \
     "{{ url('/api/v1/files') }}"</pre>

        <h3>Створити шер з лімітом 100 переглядів (curl)</h3>
<pre>curl -X POST -H "Authorization: Bearer YOUR_TOKEN" \
     "{{ url('/api/v1/files/123/share') }}"

curl -X PATCH -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"share_max_views": 100}' \
     "{{ url('/api/v1/files/123/share') }}"</pre>

        <h3>PHP (Guzzle)</h3>
<pre>$client = new \GuzzleHttp\Client([
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

$data = json_decode((string) $response-&gt;getBody(), true);</pre>

        <h3>PHP (нативний cURL)</h3>
<pre>$ch = curl_init('{{ url('/api/v1/files') }}');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER =&gt; [
        'Authorization: Bearer ' . getenv('FILEPROXY_TOKEN'),
        'Accept: application/json',
    ],
    CURLOPT_RETURNTRANSFER =&gt; true,
]);
$json = curl_exec($ch);
$files = json_decode($json, true)['data'] ?? [];</pre>

        <p style="margin-top: 32px; padding-top: 24px; border-top: 1px solid rgba(120,120,140,0.18); color: #64748b;">
            Питання чи баг? Створіть issue в репозиторії FileProxy або зверніться до адміністратора.
        </p>
    </main>
@endsection
