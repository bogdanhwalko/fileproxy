@extends('layouts.site')

@php
    $isEdit = isset($file) && $file !== null;
    $formAction = $isEdit ? route('files.update-text', $file) : route('files.store-text');
    $pageTitle = $isEdit ? 'Редагувати: '.$file->original_name : 'Створити текстовий файл';
    $oldContent = old('content', $isEdit ? ($fileContent ?? '') : '');
    $oldName = old('name', $isEdit ? $file->original_name : 'untitled.txt');
@endphp

@section('title', $pageTitle.' — FileProxy')
@section('robots', 'noindex, nofollow')

@push('head')
    {{-- CodeMirror 5: core + light theme. Tiny modes loaded below. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/eclipse.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/dialog/dialog.min.css">
@endpush

@section('content')
    <x-app-topbar title="FileProxy" subtitle="Текстовий редактор" />

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

    <section class="panel text-editor-shell" data-text-editor>
        <header class="text-editor-head">
            <a class="button secondary text-editor-back" href="{{ route('files.index', $activeFolder ? ['folder' => $activeFolder->id] : []) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                <span>Назад</span>
            </a>
            <div class="text-editor-head-title">
                <h1>
                    @if ($isEdit)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Редагування: <em>{{ $file->original_name }}</em>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="9" y1="14" x2="15" y2="14"/>
                            <line x1="9" y1="18" x2="15" y2="18"/>
                        </svg>
                        Новий текстовий файл
                    @endif
                </h1>
                <span>
                    @if ($isEdit)
                        {{ $file->human_size ?? '' }} · {{ $file->storage_label ?? '' }}
                        @if (! empty($fileTruncated))
                            · <strong style="color:#dc2626">⚠ показано лише перший 5 MB</strong>
                        @endif
                    @else
                        До 5 MB · {{ count($allowedExtensions) }} форматів з підсвіткою (php, py, js, sql, md, json, yaml, …)
                    @endif
                </span>
            </div>
        </header>

        <form action="{{ $formAction }}" method="post" class="text-editor-form" data-text-editor-form>
            @csrf
            @if ($isEdit)
                @method('patch')
            @endif

            <div class="text-editor-controls">
                <div class="text-editor-control text-editor-control-name">
                    <label for="text-editor-name">Назва файлу</label>
                    <input
                        id="text-editor-name"
                        class="field"
                        type="text"
                        name="name"
                        value="{{ $oldName }}"
                        maxlength="200"
                        placeholder="my-notes.md"
                        required
                        data-text-editor-name
                        autocomplete="off"
                    >
                </div>

                @unless ($isEdit)
                    <div class="text-editor-control">
                        <label for="text-editor-folder">Папка</label>
                        <select id="text-editor-folder" class="field" name="folder_id">
                            <option value="">Без папки</option>
                            @foreach ($folders as $folder)
                                <option value="{{ $folder->id }}" @selected(old('folder_id', $activeFolder?->id) == $folder->id)>{{ $folder->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($telegramStorageGroups->isNotEmpty() || ! $canUseLocalStorage)
                        <div class="text-editor-control">
                            <label for="text-editor-storage">Сховище</label>
                            <select id="text-editor-storage" class="field" name="telegram_storage_group_id">
                                @if ($canUseLocalStorage)
                                    <option value="">Локальне</option>
                                @endif
                                @foreach ($telegramStorageGroups as $group)
                                    <option value="{{ $group->id }}" @selected(old('telegram_storage_group_id') == $group->id || (! $canUseLocalStorage && $group->is_default))>
                                        {{ $group->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @else
                    <div class="text-editor-control">
                        <label>Папка</label>
                        <div class="field text-editor-readonly">{{ $file->folder?->name ?? 'Без папки' }}</div>
                    </div>
                    <div class="text-editor-control">
                        <label>Сховище</label>
                        <div class="field text-editor-readonly">{{ $file->storage_label ?? ($file->is_telegram ? 'Telegram' : 'Локальне') }}</div>
                    </div>
                @endunless
            </div>

            <div class="text-editor-body">
                <textarea
                    name="content"
                    class="text-editor-textarea"
                    placeholder="Почніть писати..."
                    rows="20"
                    spellcheck="false"
                    autofocus
                    data-text-editor-textarea
                    maxlength="{{ $maxBytes }}">{{ $oldContent }}</textarea>
            </div>

            <div class="text-editor-mode-row" data-text-editor-mode-row hidden>
                <label for="text-editor-mode">Підсвітка</label>
                <select id="text-editor-mode" class="field" data-text-editor-mode>
                    <option value="null">Без підсвітки (txt)</option>
                    <optgroup label="Розмітка / дані">
                        <option value="markdown">Markdown</option>
                        <option value="application/json">JSON</option>
                        <option value="yaml">YAML</option>
                        <option value="xml">XML / SVG</option>
                        <option value="htmlmixed">HTML</option>
                        <option value="properties">INI / config</option>
                        <option value="dockerfile">Dockerfile</option>
                        <option value="stex">LaTeX</option>
                        <option value="sql">SQL</option>
                    </optgroup>
                    <optgroup label="Веб">
                        <option value="javascript">JavaScript</option>
                        <option value="application/typescript">TypeScript</option>
                        <option value="jsx">JSX / TSX</option>
                        <option value="vue">Vue</option>
                        <option value="css">CSS / SCSS / LESS</option>
                    </optgroup>
                    <optgroup label="Бекенд / скрипти">
                        <option value="application/x-httpd-php">PHP</option>
                        <option value="python">Python</option>
                        <option value="ruby">Ruby</option>
                        <option value="shell">Shell / Bash</option>
                        <option value="perl">Perl</option>
                        <option value="lua">Lua</option>
                        <option value="r">R</option>
                    </optgroup>
                    <optgroup label="Системні">
                        <option value="go">Go</option>
                        <option value="rust">Rust</option>
                        <option value="text/x-java">Java</option>
                        <option value="text/x-kotlin">Kotlin</option>
                        <option value="swift">Swift</option>
                        <option value="text/x-csrc">C</option>
                        <option value="text/x-c++src">C++</option>
                        <option value="text/x-csharp">C#</option>
                    </optgroup>
                </select>
            </div>

            <footer class="text-editor-footer">
                <div class="text-editor-stats">
                    <span><strong data-text-editor-chars>0</strong> символів</span>
                    <span><strong data-text-editor-lines>1</strong> рядків</span>
                    <span><strong data-text-editor-size>0</strong> KB</span>
                </div>
                <div class="text-editor-footer-hint">
                    <kbd>Ctrl</kbd>+<kbd>S</kbd> — зберегти, <kbd>Tab</kbd> — відступ
                </div>
                <div class="text-editor-actions">
                    <a class="button secondary" href="{{ route('files.index', $activeFolder ? ['folder' => $activeFolder->id] : []) }}">Скасувати</a>
                    <button type="submit" class="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Зберегти
                    </button>
                </div>
            </footer>
        </form>
    </section>

    @push('scripts')
        {{-- Load CodeMirror core + a handful of language modes used by common
             text formats. Lazy-loaded as classic scripts from jsDelivr. --}}
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/matchbrackets.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closebrackets.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/selection/active-line.min.js" defer></script>
        {{-- Mode files: core text formats + popular programming languages.
             "clike" is the base for php/java/c/cpp; xml/htmlmixed/css/js are
             PHP's dependencies for mixed PHP/HTML files. --}}
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/clike/clike.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/php/php.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/python/python.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/ruby/ruby.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/shell/shell.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/go/go.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/rust/rust.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/swift/swift.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/markdown/markdown.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/yaml/yaml.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/sql/sql.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/stex/stex.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/jsx/jsx.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/vue/vue.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/lua/lua.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/r/r.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/properties/properties.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/dockerfile/dockerfile.min.js" defer></script>
        <script>
            window.addEventListener('load', () => {
                const shell = document.querySelector('[data-text-editor]');
                if (! shell || typeof CodeMirror === 'undefined') {
                    // Fallback to plain textarea behavior if CM didn't load
                    return initFallback();
                }

                const textarea = shell.querySelector('[data-text-editor-textarea]');
                const form      = shell.querySelector('[data-text-editor-form]');
                const charsEl   = shell.querySelector('[data-text-editor-chars]');
                const linesEl   = shell.querySelector('[data-text-editor-lines]');
                const sizeEl    = shell.querySelector('[data-text-editor-size]');
                const nameInput = shell.querySelector('[data-text-editor-name]');
                const modeSelect = shell.querySelector('[data-text-editor-mode]');
                const modeRow   = shell.querySelector('[data-text-editor-mode-row]');

                if (! textarea || ! form) return;

                /* Map filename extension → CodeMirror MIME / mode name */
                const EXT_TO_MODE = {
                    md: 'markdown',
                    markdown: 'markdown',
                    json: 'application/json',
                    js: 'javascript',
                    mjs: 'javascript',
                    ts: 'application/typescript',
                    jsx: 'jsx',
                    tsx: 'jsx',
                    vue: 'vue',
                    yaml: 'yaml',
                    yml: 'yaml',
                    xml: 'xml',
                    svg: 'xml',
                    html: 'htmlmixed',
                    htm: 'htmlmixed',
                    css: 'css',
                    scss: 'css',
                    sass: 'css',
                    less: 'css',
                    sql: 'sql',
                    tex: 'stex',
                    sh: 'shell',
                    bash: 'shell',
                    zsh: 'shell',
                    py: 'python',
                    rb: 'ruby',
                    php: 'application/x-httpd-php',
                    go: 'go',
                    rs: 'rust',
                    java: 'text/x-java',
                    kt: 'text/x-kotlin',
                    swift: 'swift',
                    c: 'text/x-csrc',
                    h: 'text/x-csrc',
                    cpp: 'text/x-c++src',
                    cc: 'text/x-c++src',
                    hpp: 'text/x-c++src',
                    cs: 'text/x-csharp',
                    r: 'r',
                    lua: 'lua',
                    pl: 'perl',
                    ini: 'properties',
                    conf: 'properties',
                    env: 'properties',
                    dockerfile: 'dockerfile',
                };

                const detectMode = (filename) => {
                    const ext = (filename || '').split('.').pop().toLowerCase();
                    return EXT_TO_MODE[ext] || 'null';
                };

                const editor = CodeMirror.fromTextArea(textarea, {
                    mode: detectMode(nameInput?.value || 'untitled.txt'),
                    theme: 'eclipse',
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 4,
                    indentWithTabs: false,
                    tabSize: 4,
                    matchBrackets: true,
                    autoCloseBrackets: true,
                    styleActiveLine: true,
                    inputStyle: 'contenteditable',
                    extraKeys: {
                        'Ctrl-S': () => form.requestSubmit(),
                        'Cmd-S':  () => form.requestSubmit(),
                        'Tab': (cm) => {
                            if (cm.somethingSelected()) cm.indentSelection('add');
                            else cm.replaceSelection('    ', 'end');
                        },
                    },
                });

                // Sync CodeMirror → textarea before form submit
                form.addEventListener('submit', () => editor.save());

                // Live stats from editor content
                const updateStats = () => {
                    const text = editor.getValue();
                    const chars = text.length;
                    const lines = editor.lineCount();
                    const bytes = new Blob([text]).size;
                    if (charsEl) charsEl.textContent = chars.toLocaleString('uk-UA');
                    if (linesEl) linesEl.textContent = lines.toLocaleString('uk-UA');
                    if (sizeEl)  sizeEl.textContent  = (bytes / 1024).toFixed(1);
                };
                editor.on('change', updateStats);
                updateStats();

                // Detect mode from filename on rename (blur)
                if (nameInput) {
                    const applyDetectedMode = () => {
                        const mode = detectMode(nameInput.value);
                        editor.setOption('mode', mode);
                        if (modeSelect) modeSelect.value = mode;
                    };
                    nameInput.addEventListener('blur', () => {
                        const v = nameInput.value.trim();
                        if (v !== '' && ! v.includes('.')) {
                            nameInput.value = v + '.txt';
                        }
                        applyDetectedMode();
                    });
                    nameInput.addEventListener('input', applyDetectedMode);
                }

                // Manual mode switch via dropdown
                if (modeRow) modeRow.hidden = false;
                if (modeSelect) {
                    modeSelect.value = detectMode(nameInput?.value || 'untitled.txt');
                    modeSelect.addEventListener('change', () => {
                        editor.setOption('mode', modeSelect.value);
                        editor.focus();
                    });
                }

                // Refresh editor sizing after layout settles
                requestAnimationFrame(() => editor.refresh());

                /* ---- Fallback: plain textarea if CM didn't load ---- */
                function initFallback() {
                    const textarea = shell?.querySelector('[data-text-editor-textarea]');
                    const form     = shell?.querySelector('[data-text-editor-form]');
                    const charsEl  = shell?.querySelector('[data-text-editor-chars]');
                    const linesEl  = shell?.querySelector('[data-text-editor-lines]');
                    const sizeEl   = shell?.querySelector('[data-text-editor-size]');
                    if (! textarea || ! form) return;

                    const updateStats = () => {
                        const text = textarea.value;
                        if (charsEl) charsEl.textContent = text.length.toLocaleString('uk-UA');
                        if (linesEl) linesEl.textContent = (text === '' ? 1 : text.split('\n').length).toLocaleString('uk-UA');
                        if (sizeEl)  sizeEl.textContent  = (new Blob([text]).size / 1024).toFixed(1);
                    };
                    textarea.addEventListener('input', updateStats);
                    updateStats();

                    textarea.addEventListener('keydown', (e) => {
                        if (e.key === 'Tab' && ! e.shiftKey) {
                            e.preventDefault();
                            const start = textarea.selectionStart;
                            const end   = textarea.selectionEnd;
                            textarea.value = textarea.value.slice(0, start) + '\t' + textarea.value.slice(end);
                            textarea.selectionStart = textarea.selectionEnd = start + 1;
                            updateStats();
                        }
                    });
                    shell.addEventListener('keydown', (e) => {
                        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                            e.preventDefault();
                            form.requestSubmit();
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
