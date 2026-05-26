(() => {
    if (window.__fpPaletteBound) return;
    window.__fpPaletteBound = true;

    const root = document.querySelector('[data-palette]');
    if (! root) return;

    const input    = root.querySelector('[data-palette-input]');
    const results  = root.querySelector('[data-palette-results]');
    const emptyEl  = root.querySelector('[data-palette-empty]');
    const hintEl   = root.querySelector('[data-palette-hint]');

    const SEARCH_URL = root.dataset.paletteSearchUrl;
    const FILES_URL  = root.dataset.paletteFilesUrl;
    const STATS_URL  = root.dataset.paletteStatsUrl;
    const TG_URL     = root.dataset.paletteTgUrl;

    const ICONS = {
        file:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
        folder: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>',
        tag:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
        chart:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>',
        send:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>',
        upload: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
        grid:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.2"/><rect x="14" y="3" width="7" height="7" rx="1.2"/><rect x="3" y="14" width="7" height="7" rx="1.2"/><rect x="14" y="14" width="7" height="7" rx="1.2"/></svg>',
        list:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
    };

    const setDensity = (v) => {
        try { localStorage.setItem('fp-density', v); } catch (e) { /* private mode */ }
        ['comfortable', 'compact', 'list'].forEach((d) => document.body.classList.toggle('density-' + d, d === v));
        document.querySelectorAll('[data-density-toggle] [data-density]').forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.density === v);
        });
    };

    const ACTIONS = [
        { label: 'Усі файли',           icon: 'file',   href: FILES_URL,    keywords: 'home головна index files' },
        { label: 'Статистика сховища',  icon: 'chart',  href: STATS_URL,    keywords: 'stats місце розподіл графік' },
        { label: 'Telegram сховище',    icon: 'send',   href: TG_URL,       keywords: 'telegram бот група' },
        { label: 'Без папки',           icon: 'folder', href: FILES_URL + '?folder=root', keywords: 'root корінь' },
        { label: 'Завантажити файли',   icon: 'upload', href: FILES_URL,    keywords: 'upload новий додати', onPick: () => setTimeout(() => document.querySelector('[data-upload-input]')?.click(), 250) },
        { label: 'Щільність: Комфортно', icon: 'grid', run: () => setDensity('comfortable'), keywords: 'density розмір comfortable' },
        { label: 'Щільність: Компактно', icon: 'grid', run: () => setDensity('compact'),     keywords: 'density розмір compact' },
        { label: 'Щільність: Списком',   icon: 'list', run: () => setDensity('list'),        keywords: 'density розмір list' },
    ];

    let items = [];
    let selected = -1;
    let activeAbort = null;
    let debounceTimer = null;

    const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c]);

    const highlight = (text, q) => {
        if (! q) return escapeHtml(text);
        const lower = text.toLowerCase();
        const ql = q.toLowerCase();
        const i = lower.indexOf(ql);
        if (i < 0) return escapeHtml(text);
        return escapeHtml(text.slice(0, i))
            + '<mark>' + escapeHtml(text.slice(i, i + ql.length)) + '</mark>'
            + escapeHtml(text.slice(i + ql.length));
    };

    const makeItem = ({ icon, label, meta, badge, gradient, run, q }) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fp-palette-item';
        btn.innerHTML = `
            <span class="fp-palette-item-icon" ${gradient ? `style="background-image:${gradient}"` : ''}>${ICONS[icon] || ICONS.file}</span>
            <span class="fp-palette-item-body">
                <span class="fp-palette-item-label">${highlight(label, q)}</span>
                ${meta ? `<span class="fp-palette-item-meta">${escapeHtml(meta)}</span>` : ''}
            </span>
            ${badge !== undefined && badge !== null && badge !== '' ? `<span class="fp-palette-item-badge">${escapeHtml(badge)}</span>` : ''}
        `;
        btn._palRun = run;
        return btn;
    };

    const makeSection = (title, descriptors, q) => {
        if (! descriptors.length) return null;
        const wrap = document.createElement('div');
        wrap.className = 'fp-palette-section';
        const head = document.createElement('div');
        head.className = 'fp-palette-section-head';
        head.textContent = title;
        wrap.appendChild(head);
        descriptors.forEach((d) => wrap.appendChild(makeItem({ ...d, q })));
        return wrap;
    };

    const filterActions = (q) => {
        if (! q) return ACTIONS;
        const ql = q.toLowerCase();
        return ACTIONS.filter((a) => a.label.toLowerCase().includes(ql) || (a.keywords || '').toLowerCase().includes(ql));
    };

    const clearResults = () => {
        Array.from(results.children).forEach((c) => {
            if (c !== emptyEl && c !== hintEl) c.remove();
        });
    };

    const render = (q, data) => {
        clearResults();

        const filesList = (data?.files || []).map((f) => ({
            icon: 'file', label: f.name, gradient: f.gradient,
            meta: [f.label, f.size, f.protected ? '🔒' : null].filter(Boolean).join(' · '),
            run: () => window.location.href = f.preview,
        }));
        const foldersList = (data?.folders || []).map((f) => ({
            icon: 'folder', label: f.name, badge: f.count,
            run: () => window.location.href = f.href,
        }));
        const tagsList = (data?.tags || []).map((t) => ({
            icon: 'tag', label: '#' + t.name, badge: t.count,
            run: () => window.location.href = t.href,
        }));
        const actionsList = filterActions(q).map((a) => ({
            icon: a.icon, label: a.label,
            run: a.run ? a.run : () => { if (a.onPick) a.onPick(); window.location.href = a.href; },
        }));

        const sections = [
            ['Файли',  filesList],
            ['Папки',  foldersList],
            ['Теги',   tagsList],
            ['Дії',    actionsList],
        ];

        sections.forEach(([title, list]) => {
            const node = makeSection(title, list, q);
            if (node) results.insertBefore(node, emptyEl);
        });

        items = Array.from(results.querySelectorAll('.fp-palette-item'));
        hintEl.hidden = q.length > 0;
        emptyEl.hidden = items.length > 0 || ! q;

        selected = items.length > 0 ? 0 : -1;
        updateSelection();
    };

    const updateSelection = () => {
        items.forEach((el, i) => el.classList.toggle('is-selected', i === selected));
        if (selected >= 0 && items[selected]) {
            items[selected].scrollIntoView({ block: 'nearest' });
        }
    };

    const runQuery = (q) => {
        if (activeAbort) activeAbort.abort();

        if (! q) {
            render('', null);
            return;
        }

        activeAbort = new AbortController();
        const url = SEARCH_URL + '?q=' + encodeURIComponent(q);
        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: activeAbort.signal,
            credentials: 'same-origin',
        })
        .then((r) => r.ok ? r.json() : Promise.reject(new Error('http ' + r.status)))
        .then((data) => render(q, data))
        .catch((e) => {
            if (e.name === 'AbortError') return;
            render(q, null);
        });
    };

    const open = () => {
        if (! root.hidden) return;
        root.hidden = false;
        requestAnimationFrame(() => {
            root.classList.add('is-open');
            input.focus();
            input.select();
        });
        document.documentElement.style.overflow = 'hidden';
        if (! input.value) render('', null);
    };

    const close = () => {
        root.classList.remove('is-open');
        document.documentElement.style.overflow = '';
        setTimeout(() => {
            if (! root.classList.contains('is-open')) root.hidden = true;
        }, 160);
    };

    const activate = (idx) => {
        const el = items[idx];
        if (! el || typeof el._palRun !== 'function') return;
        close();
        el._palRun();
    };

    input.addEventListener('input', () => {
        const q = input.value.trim();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => runQuery(q), q.length === 0 ? 0 : 140);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length === 0) return;
            selected = (selected + 1) % items.length;
            updateSelection();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length === 0) return;
            selected = (selected - 1 + items.length) % items.length;
            updateSelection();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selected >= 0) activate(selected);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            close();
        }
    });

    results.addEventListener('click', (e) => {
        const btn = e.target.closest('.fp-palette-item');
        if (! btn) return;
        const idx = items.indexOf(btn);
        if (idx >= 0) activate(idx);
    });

    results.addEventListener('pointermove', (e) => {
        const btn = e.target.closest('.fp-palette-item');
        if (! btn) return;
        const idx = items.indexOf(btn);
        if (idx >= 0 && idx !== selected) {
            selected = idx;
            updateSelection();
        }
    });

    root.addEventListener('click', (e) => {
        if (e.target.closest('[data-palette-close]')) {
            e.preventDefault();
            close();
        }
    });

    document.addEventListener('keydown', (e) => {
        const isK = e.key === 'k' || e.key === 'K' || e.key === 'л' || e.key === 'Л';
        if (isK && (e.metaKey || e.ctrlKey)) {
            // Don't intercept when typing in an input/textarea/contenteditable (unless modal is already open)
            if (! root.hidden) { e.preventDefault(); close(); return; }
            e.preventDefault();
            open();
        }
    });

    // Floating "press Cmd+K" hint button could go here in future
})();
