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
