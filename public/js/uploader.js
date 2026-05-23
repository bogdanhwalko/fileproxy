/*
 * FileProxy uploader
 * Per-file XHR upload with progress, status polling, and Wake Lock.
 *
 * Listens (via document delegation) for submits on any [data-fp-uploader] form.
 * All DOM elements (form, widget, etc.) are resolved lazily at submit time,
 * so this script survives DOM replacements and late-rendered markup.
 *
 * Form must have: action, data-status-url ("/files/__id__/status"), file input
 * named "files[]". Optional: hidden inputs folder_id, telegram_storage_group_id.
 *
 * Widget markup must exist with [data-fp-uploader-widget] +
 * [data-fp-uploader-list], optionally [data-fp-uploader-summary],
 * [data-fp-uploader-minimize], [data-fp-uploader-close].
 */

(() => {
    const POLL_INTERVAL_MS = 3000;
    const POLL_MAX_ATTEMPTS = 60; // 3 min
    const CONCURRENCY = 2;
    const DEFAULT_MAX_FILE_MB = 50; // Telegram Bot API sendDocument limit (raise via self-hosted bot-api server)
    const MAX_RETRY_ATTEMPTS = 5;   // per-file retry budget for 429 / transient network failures

    /* ----------------------------------------------------
       State
       ---------------------------------------------------- */
    let items = [];           // [{ id, file, status, progress, error, serverId, attempts }]
    let active = 0;
    let wakeLock = null;
    let wakeLockRequested = false;
    let currentEndpoint = null;
    let currentStatusUrlTpl = null;
    let currentCsrf = null;
    let currentMaxBytes = DEFAULT_MAX_FILE_MB * 1024 * 1024;
    let pauseUntil = 0;       // epoch ms; while > now, queue workers wait

    /* ----------------------------------------------------
       Lazy element resolution
       ---------------------------------------------------- */
    function widget()      { return document.querySelector('[data-fp-uploader-widget]'); }
    function listEl()      { return widget()?.querySelector('[data-fp-uploader-list]') || null; }
    function summaryEl()   { return widget()?.querySelector('[data-fp-uploader-summary]') || null; }
    function csrfToken()   { return document.querySelector('meta[name="csrf-token"]')?.content || ''; }

    /* ----------------------------------------------------
       Wake Lock
       ---------------------------------------------------- */
    async function acquireWakeLock() {
        if (wakeLockRequested) return;
        if (!('wakeLock' in navigator)) return;

        wakeLockRequested = true;
        try {
            wakeLock = await navigator.wakeLock.request('screen');
            wakeLock.addEventListener('release', () => { wakeLock = null; });
        } catch (e) {
            wakeLock = null;
        }
    }

    function releaseWakeLock() {
        wakeLockRequested = false;
        if (wakeLock) {
            try { wakeLock.release(); } catch (_) {}
            wakeLock = null;
        }
    }

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && hasActiveWork()) {
            wakeLockRequested = false;
            acquireWakeLock();
        }
    });

    function hasActiveWork() {
        return items.some(i => i.status === 'queued' || i.status === 'uploading' || i.status === 'pending');
    }

    /* ----------------------------------------------------
       Rendering
       ---------------------------------------------------- */
    function renderItem(item) {
        const list = listEl();
        if (!list) return;

        let row = list.querySelector(`[data-item-id="${item.id}"]`);
        if (!row) {
            row = document.createElement('div');
            row.className = 'fp-up-item';
            row.dataset.itemId = item.id;
            row.innerHTML = `
                <div class="fp-up-item-icon" aria-hidden="true"></div>
                <div class="fp-up-item-body">
                    <div class="fp-up-item-name"></div>
                    <div class="fp-up-item-bar"><div class="fp-up-item-bar-fill"></div></div>
                    <div class="fp-up-item-meta"></div>
                </div>
                <div class="fp-up-item-status"></div>
            `;
            list.appendChild(row);
        }
        row.dataset.status = item.status;
        row.querySelector('.fp-up-item-name').textContent = item.file.name;
        row.querySelector('.fp-up-item-bar-fill').style.width = `${item.progress}%`;
        row.querySelector('.fp-up-item-icon').innerHTML = iconForStatus(item.status);
        row.querySelector('.fp-up-item-status').innerHTML = statusLabel(item);
        const meta = row.querySelector('.fp-up-item-meta');
        meta.textContent = metaLine(item);
        meta.title = (item.status === 'failed' && item.error) ? item.error : '';
    }

    function iconForStatus(status) {
        switch (status) {
            case 'uploaded':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
            case 'failed':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
            case 'pending':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
            case 'uploading':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>';
            default:
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
        }
    }

    function statusLabel(item) {
        switch (item.status) {
            case 'queued':
                if (isPaused()) {
                    const sec = Math.max(1, Math.ceil((pauseUntil - Date.now()) / 1000));
                    return `<span class="fp-up-pill is-pause">Пауза ${sec}с</span>`;
                }
                return '<span class="fp-up-pill">У черзі</span>';
            case 'uploading': return `<span class="fp-up-pct">${Math.round(item.progress)}%</span>`;
            case 'pending':   return '<span class="fp-up-pill is-pending">Telegram…</span>';
            case 'uploaded':  return '<span class="fp-up-pill is-ok">Готово</span>';
            case 'failed':    return '<span class="fp-up-pill is-fail">Помилка</span>';
            default:          return '';
        }
    }

    function isPaused() {
        return Date.now() < pauseUntil;
    }

    function setPause(seconds) {
        const target = Date.now() + Math.max(1, seconds) * 1000;
        if (target > pauseUntil) pauseUntil = target;
    }

    function metaLine(item) {
        const sizeText = formatBytes(item.file.size);
        if (item.status === 'failed' && item.error) {
            return `${sizeText} · ${item.error}`;
        }
        return sizeText;
    }

    function formatBytes(bytes) {
        if (!bytes || bytes <= 0) return '0 KB';
        const units = ['B', 'KB', 'MB', 'GB'];
        const i = Math.min(units.length - 1, Math.floor(Math.log(bytes) / Math.log(1024)));
        const value = bytes / Math.pow(1024, i);
        return `${value.toFixed(value < 10 && i > 0 ? 1 : 0)} ${units[i]}`;
    }

    function renderSummary() {
        const w = widget();
        const s = summaryEl();
        const total = items.length;
        const done = items.filter(i => i.status === 'uploaded').length;
        const failed = items.filter(i => i.status === 'failed').length;
        const inFlight = items.filter(i => i.status === 'uploading' || i.status === 'pending').length;
        const queued = items.filter(i => i.status === 'queued').length;
        const paused = isPaused();

        let line;
        if (paused && (queued > 0 || inFlight > 0)) {
            const sec = Math.max(1, Math.ceil((pauseUntil - Date.now()) / 1000));
            line = `Пауза ${sec}с (ліміт API). ${done + failed} з ${total} оброблено${failed > 0 ? ` · помилок: ${failed}` : ''}`;
        } else if (inFlight + queued > 0) {
            line = `Завантаження: ${done + failed} з ${total}${failed > 0 ? ` · помилок: ${failed}` : ''}`;
        } else if (failed > 0 && done > 0) {
            line = `Готово: ${done} · з помилкою: ${failed}`;
        } else if (failed > 0) {
            line = `З помилкою: ${failed} з ${total}`;
        } else {
            line = `Усі ${total} ${pluralizeFiles(total)} завантажено`;
        }
        if (s) s.textContent = line;
        if (w) {
            w.dataset.state = paused
                ? 'paused'
                : ((inFlight + queued > 0) ? 'active' : (failed > 0 ? 'with-failures' : 'done'));
        }
    }

    function pluralizeFiles(n) {
        const mod10 = n % 10;
        const mod100 = n % 100;
        if (mod10 === 1 && mod100 !== 11) return 'файл';
        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return 'файли';
        return 'файлів';
    }

    function showWidget() {
        const w = widget();
        if (!w) return;
        w.hidden = false;
        w.classList.remove('is-collapsed');
    }

    function clearList() {
        items = [];
        const list = listEl();
        if (list) list.innerHTML = '';
        const w = widget();
        if (w) {
            w.hidden = true;
            w.classList.remove('is-collapsed');
        }
    }

    /* ----------------------------------------------------
       Upload one file via XHR
       ---------------------------------------------------- */
    function uploadOne(item, formExtras) {
        return new Promise((resolve) => {
            const maxMb = Math.round(currentMaxBytes / 1024 / 1024);

            if (item.file && item.file.size > currentMaxBytes) {
                item.status = 'failed';
                item.progress = 0;
                item.error = `Файл більше ${maxMb} МБ — Telegram Bot API не приймає такі файли. Завантажте файл вручну у групу через Telegram, або поділіть його на частини.`;
                renderItem(item);
                renderSummary();
                resolve();
                return;
            }

            const fd = new FormData();
            fd.append('files[]', item.file);
            if (formExtras.folder_id) fd.append('folder_id', formExtras.folder_id);
            if (formExtras.telegram_storage_group_id) fd.append('telegram_storage_group_id', formExtras.telegram_storage_group_id);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', currentEndpoint, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', currentCsrf || csrfToken());

            item.status = 'uploading';
            item.progress = 0;
            renderItem(item);
            renderSummary();

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    item.progress = (e.loaded / e.total) * 100;
                    renderItem(item);
                }
            };

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    let json = {};
                    try { json = JSON.parse(xhr.responseText); } catch (_) {}
                    const created = (json.files && json.files[0]) || null;
                    if (!created) {
                        item.status = 'failed';
                        item.error = 'Неочікувана відповідь сервера';
                    } else {
                        item.serverId = created.id;
                        if (created.status === 'uploaded') {
                            item.status = 'uploaded';
                            item.progress = 100;
                        } else if (created.status === 'failed') {
                            item.status = 'failed';
                            item.error = created.upload_failure_reason || 'Завантаження в Telegram не вдалося';
                        } else {
                            item.status = 'pending';
                            item.progress = 100;
                            pollStatus(item);
                        }
                    }
                } else if (xhr.status === 422) {
                    let json = {};
                    try { json = JSON.parse(xhr.responseText); } catch (_) {}
                    item.status = 'failed';
                    item.error = json.message || extractFirstError(json.errors) || 'Помилка валідації';
                } else if (xhr.status === 429) {
                    // Rate limit hit. Respect server's Retry-After if present, otherwise wait 15s.
                    // Re-queue the file and pause all workers until the window passes.
                    const retryAfterHdr = xhr.getResponseHeader('Retry-After');
                    const retryAfter = retryAfterHdr ? parseInt(retryAfterHdr, 10) : 15;
                    const wait = Math.max(5, Math.min(120, isNaN(retryAfter) ? 15 : retryAfter));

                    item.attempts = (item.attempts || 0) + 1;

                    if (item.attempts <= MAX_RETRY_ATTEMPTS) {
                        setPause(wait);
                        item.status = 'queued';
                        item.progress = 0;
                        item.error = null;
                        renderItem(item);
                        renderSummary();
                        resolve();
                        return;
                    }

                    item.status = 'failed';
                    item.error = `Перевищено ліміт запитів (429) після ${MAX_RETRY_ATTEMPTS} спроб. Спробуйте пізніше.`;
                } else if (xhr.status === 0) {
                    item.status = 'failed';
                    item.error = 'З\'єднання обірвано';
                } else {
                    item.status = 'failed';
                    item.error = `HTTP ${xhr.status}`;
                }
                renderItem(item);
                renderSummary();
                resolve();
            };

            xhr.onerror = () => {
                item.status = 'failed';
                item.error = 'Помилка мережі';
                renderItem(item);
                renderSummary();
                resolve();
            };

            xhr.send(fd);
        });
    }

    function extractFirstError(errors) {
        if (!errors || typeof errors !== 'object') return null;
        for (const k of Object.keys(errors)) {
            const v = errors[k];
            if (Array.isArray(v) && v.length) return v[0];
            if (typeof v === 'string') return v;
        }
        return null;
    }

    /* ----------------------------------------------------
       Status polling for Telegram-pending files
       ---------------------------------------------------- */
    function pollStatus(item) {
        if (!item.serverId || !currentStatusUrlTpl) return;
        const url = currentStatusUrlTpl.replace('__id__', String(item.serverId));
        let attempts = 0;

        const tick = async () => {
            attempts++;
            try {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.status === 'uploaded') {
                        item.status = 'uploaded';
                        renderItem(item);
                        renderSummary();
                        return;
                    }
                    if (data.status === 'failed') {
                        item.status = 'failed';
                        item.error = data.upload_failure_reason || 'Завантаження в Telegram не вдалося';
                        renderItem(item);
                        renderSummary();
                        return;
                    }
                }
            } catch (_) {}

            if (attempts < POLL_MAX_ATTEMPTS) {
                setTimeout(tick, POLL_INTERVAL_MS);
            } else {
                item.status = 'failed';
                item.error = 'Timeout — перевірте стан пізніше';
                renderItem(item);
                renderSummary();
            }
        };

        setTimeout(tick, POLL_INTERVAL_MS);
    }

    /* ----------------------------------------------------
       Queue scheduler
       ---------------------------------------------------- */
    async function processQueue(formExtras) {
        await acquireWakeLock();

        const runOne = async () => {
            // If a server-imposed pause is in effect, wait it out before picking the next file.
            if (isPaused()) {
                const wait = Math.max(250, pauseUntil - Date.now() + 50);
                renderSummary();
                items.filter(i => i.status === 'queued').forEach(renderItem);
                setTimeout(runOne, Math.min(wait, 1000));
                return;
            }

            const next = items.find(i => i.status === 'queued');
            if (!next) {
                if (active === 0 && !items.some(i => i.status === 'queued' || i.status === 'uploading')) {
                    finishedAllUploads();
                }
                return;
            }
            active++;
            try {
                await uploadOne(next, formExtras);
            } finally {
                active--;
                if (items.some(i => i.status === 'queued')) {
                    runOne();
                } else if (active === 0) {
                    finishedAllUploads();
                }
            }
        };

        for (let i = 0; i < CONCURRENCY; i++) {
            runOne();
        }

        // Keep summary in sync during pause countdowns
        startPauseTicker();
    }

    let pauseTickerInterval = null;

    function startPauseTicker() {
        if (pauseTickerInterval) return;
        pauseTickerInterval = setInterval(() => {
            if (!hasActiveWork() && !isPaused()) {
                clearInterval(pauseTickerInterval);
                pauseTickerInterval = null;
                return;
            }
            if (isPaused()) {
                renderSummary();
                items.filter(i => i.status === 'queued').forEach(renderItem);
            }
        }, 1000);
    }

    function finishedAllUploads() {
        const pending = items.some(i => i.status === 'pending');

        const onAllSettled = () => {
            releaseWakeLock();
            window.dispatchEvent(new CustomEvent('fp-uploader:refresh-needed'));
            // All rows stay visible until the user closes the widget manually.
        };

        if (!pending) {
            onAllSettled();
        } else {
            const watcher = setInterval(() => {
                if (!items.some(i => i.status === 'pending')) {
                    clearInterval(watcher);
                    onAllSettled();
                }
            }, 1500);
        }
    }

    /* ----------------------------------------------------
       Submit interception — delegated on document so it survives
       form replacements and runs regardless of DOM-ready timing.
       ---------------------------------------------------- */
    document.addEventListener('submit', (e) => {
        const form = e.target.closest && e.target.closest('[data-fp-uploader]');
        if (!form) return;

        const fileInput = form.querySelector('input[type="file"][name="files[]"]');
        if (!fileInput || !fileInput.files || !fileInput.files.length) return;

        const endpoint     = form.getAttribute('action');
        const statusUrlTpl = form.dataset.statusUrl;
        const csrf         = csrfToken();
        const w            = widget();
        const list         = listEl();

        // Required for the widget to function. If anything is missing, log and let the
        // browser do a native submit (graceful fallback).
        if (!endpoint || !statusUrlTpl || !csrf || !w || !list) {
            console.warn('FileProxy uploader: missing config — falling back to native form submit', {
                endpoint, statusUrlTpl, hasCsrf: !!csrf, hasWidget: !!w, hasList: !!list,
            });
            return;
        }

        e.preventDefault();

        currentEndpoint = endpoint;
        currentStatusUrlTpl = statusUrlTpl;
        currentCsrf = csrf;

        const maxMbAttr = parseInt(form.dataset.maxFileMb || '', 10);
        currentMaxBytes = (Number.isFinite(maxMbAttr) && maxMbAttr > 0 ? maxMbAttr : DEFAULT_MAX_FILE_MB) * 1024 * 1024;

        const folderId = form.querySelector('[name="folder_id"]')?.value || '';
        const groupId = form.querySelector('[name="telegram_storage_group_id"]')?.value || '';

        try {
            if (groupId !== '') {
                window.localStorage?.setItem('fp_last_storage_group_id', groupId);
            } else {
                window.localStorage?.removeItem('fp_last_storage_group_id');
            }
        } catch (_) {}

        const newItems = Array.from(fileInput.files).map((file) => ({
            id: `i${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            file,
            status: 'queued',
            progress: 0,
            error: null,
            serverId: null,
            attempts: 0,
        }));

        items = items.filter(i => i.status === 'pending' || i.status === 'uploading').concat(newItems);
        showWidget();
        newItems.forEach(renderItem);
        renderSummary();

        // Clear file input + any "selected files" UI hooked by older code
        fileInput.value = '';
        const clearBtn = form.querySelector('[data-upload-clear]');
        if (clearBtn) {
            try { clearBtn.click(); } catch (_) {}
        }
        form.dispatchEvent(new Event('fp-uploader:enqueued'));

        processQueue({
            folder_id: folderId,
            telegram_storage_group_id: groupId,
        });
    });

    /* ----------------------------------------------------
       Widget controls (delegated)
       ---------------------------------------------------- */
    document.addEventListener('click', (e) => {
        if (e.target.closest && e.target.closest('[data-fp-uploader-minimize]')) {
            const w = widget();
            if (w) w.classList.toggle('is-collapsed');
            return;
        }

        if (e.target.closest && e.target.closest('[data-fp-uploader-close]')) {
            if (hasActiveWork()) {
                const ok = confirm('Активні завантаження ще тривають. Якщо закрити віджет, прогрес буде втрачено. Закрити?');
                if (!ok) return;
            }
            releaseWakeLock();
            clearList();
        }
    });

    // Warn user before leaving page if uploads in progress
    window.addEventListener('beforeunload', (e) => {
        if (hasActiveWork()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
