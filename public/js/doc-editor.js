/* Doc editor — Quill WYSIWYG bound to [data-doc-editor].
   Loads initial HTML from a hidden textarea (data-doc-editor-content),
   serializes Quill HTML back to that textarea before form submit.
   Saved server-side as a self-contained .html document.

   Toolbar: headings 1-3, bold/italic/underline/strike, color/background,
   lists, indent, quote, code, links, images (data URI), align, clean. */
window.addEventListener('load', () => {
    const shell = document.querySelector('[data-doc-editor]');
    if (! shell || typeof Quill === 'undefined') {
        // Fallback: convert hidden textarea to a normal one if Quill failed
        const ta = shell?.querySelector('[data-doc-editor-content]');
        if (ta) {
            ta.hidden = false;
            ta.rows = 16;
            ta.style.fontFamily = 'ui-monospace, Consolas, Menlo, monospace';
            ta.style.width = '100%';
        }
        return;
    }

    const editorEl  = shell.querySelector('[data-doc-editor-quill]');
    const hiddenTa  = shell.querySelector('[data-doc-editor-content]');
    const form      = shell.querySelector('[data-doc-editor-form]');
    const wordsEl   = shell.querySelector('[data-doc-editor-words]');
    const charsEl   = shell.querySelector('[data-doc-editor-chars]');
    const titleInput = shell.querySelector('[data-doc-editor-title]');

    if (! editorEl || ! hiddenTa || ! form) return;

    const toolbar = [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ color: [] }, { background: [] }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ indent: '-1' }, { indent: '+1' }],
        [{ align: [] }],
        ['blockquote', 'code-block'],
        ['link', 'image'],
        ['clean'],
    ];

    const quill = new Quill(editorEl, {
        theme: 'snow',
        placeholder: 'Почніть писати документ...',
        modules: {
            toolbar,
            keyboard: {
                bindings: {
                    save: {
                        key: 'S',
                        shortKey: true,
                        handler: () => { form.requestSubmit(); return false; },
                    },
                },
            },
        },
    });

    // Load initial content from the hidden textarea (HTML)
    const initial = hiddenTa.value || '';
    if (initial.trim() !== '') {
        // Use the clipboard module to parse HTML safely
        quill.clipboard.dangerouslyPasteHTML(0, initial);
    }

    // Stats updater
    const updateStats = () => {
        const text  = quill.getText();           // plain text from editor
        const chars = text.replace(/\n+$/, '').length;
        const words = (text.match(/\b[\p{L}\d]+\b/gu) || []).length;
        if (wordsEl) wordsEl.textContent = words.toLocaleString('uk-UA');
        if (charsEl) charsEl.textContent = chars.toLocaleString('uk-UA');
    };
    quill.on('text-change', updateStats);
    updateStats();

    // Sync HTML back to hidden textarea before form submit
    form.addEventListener('submit', () => {
        hiddenTa.value = quill.getSemanticHTML();
    });

    // Title-based default filename (if title empty, suggest "Untitled")
    if (titleInput) {
        titleInput.addEventListener('blur', () => {
            if (titleInput.value.trim() === '') titleInput.value = 'Untitled document';
        });
    }

    // Ctrl/Cmd+S on the whole shell (in case focus is outside Quill)
    shell.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            form.requestSubmit();
        }
    });
});
