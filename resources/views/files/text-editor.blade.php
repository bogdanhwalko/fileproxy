@extends('layouts.site')

@section('title', 'Створити текстовий файл — FileProxy')
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="9" y1="14" x2="15" y2="14"/>
                        <line x1="9" y1="18" x2="15" y2="18"/>
                    </svg>
                    Новий текстовий файл
                </h1>
                <span>До 5 MB · форматы: {{ implode(', ', $allowedExtensions) }}</span>
            </div>
        </header>

        <form action="{{ route('files.store-text') }}" method="post" class="text-editor-form" data-text-editor-form>
            @csrf

            <div class="text-editor-controls">
                <div class="text-editor-control text-editor-control-name">
                    <label for="text-editor-name">Назва файлу</label>
                    <input
                        id="text-editor-name"
                        class="field"
                        type="text"
                        name="name"
                        value="{{ old('name', 'untitled.txt') }}"
                        maxlength="200"
                        placeholder="my-notes.md"
                        required
                        data-text-editor-name
                        autocomplete="off"
                    >
                </div>

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
                    maxlength="{{ $maxBytes }}">{{ old('content') }}</textarea>
            </div>

            <div class="text-editor-mode-row" data-text-editor-mode-row hidden>
                <label for="text-editor-mode">Підсвітка</label>
                <select id="text-editor-mode" class="field" data-text-editor-mode>
                    <option value="null">Без підсвітки (txt)</option>
                    <option value="markdown">Markdown</option>
                    <option value="application/json">JSON</option>
                    <option value="yaml">YAML</option>
                    <option value="xml">XML / HTML</option>
                    <option value="sql">SQL</option>
                    <option value="javascript">JavaScript</option>
                    <option value="css">CSS</option>
                    <option value="stex">LaTeX</option>
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
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/markdown/markdown.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/yaml/yaml.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/sql/sql.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/stex/stex.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js" defer></script>
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
                    ts: 'javascript',
                    yaml: 'yaml',
                    yml: 'yaml',
                    xml: 'xml',
                    html: 'htmlmixed',
                    htm: 'htmlmixed',
                    css: 'css',
                    sql: 'sql',
                    tex: 'stex',
                    sh: 'shell',
                    py: 'python',
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
