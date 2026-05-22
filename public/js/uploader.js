/*
 * FileProxy uploader
 * Per-file XHR upload with progress, status polling, and Wake Lock.
 *
 * Wires up automatically when there's a [data-fp-uploader] form on the page.
 * Form data: standard file input named "files[]" + folder_id + telegram_storage_group_id.
 */

(() => {
    const form = document.querySelector('[data-fp-uploader]');
    if (!form) return;

    const widget       = document.querySelector('[data-fp-uploader-widget]');
    const listEl       = widget?.querySelector('[data-fp-uploader-list]');
    const summaryEl    = widget?.querySelector('[data-fp-uploader-summary]');
    const minimizeBtn  = widget?.querySelector('[data-fp-uploader-minimize]');
    const closeBtn     = widget?.querySelector('[data-fp-uploader-close]');
    const csrf         = document.querySelector('meta[name="csrf-token"]')?.content;
    const endpoint     = form.getAttribute('action');
    const statusUrlTpl = form.dataset.statusUrl;        // "/files/__id__/status"
    const reloadUrl    = form.dataset.reloadUrl || null;
    const POLL_INTERVAL_MS = 3000;
    const POLL_MAX_ATTEMPTS = 60; // 3 хв
    const CONCURRENCY = 2;

    if (!widget || !listEl || !csrf || !endpoint || !statusUrlTpl) {
        console.warn('FileProxy uploader: missing required DOM/config; falling back to native form');
        return;
    }

    /* ----------------------------------------------------
       State
       ---------------------------------------------------- */
    let items = [];           // [{ id, file, status, progress, error, serverId }]
    let active = 0;
    let wakeLock = null;
    let wakeLockRequested = false;

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
            // permission denied or unsupported context
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

    // Re-acquire wake lock on visibility change while active uploads exist
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
        let row = listEl.querySelector(`[data-item-id="${item.id}"]`);
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
            listEl.appendChild(row);
        }
        row.dataset.status = item.status;
        row.querySelector('.fp-up-item-name').textContent = item.file.name;
        row.querySelector('.fp-up-item-bar-fill').style.width = `${item.progress}%`;
        row.querySelector('.fp-up-item-icon').innerHTML = iconForStatus(item.status);
        row.querySelector('.fp-up-item-status').innerHTML = statusLabel(item);
        row.querySelector('.fp-up-item-meta').textContent = metaLine(item);
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
            case 'queued':    return '<span class="fp-up-pill">У черзі</span>';
            case 'uploading': return `<span class="fp-up-pct">${Math.round(item.progress)}%</span>`;
            case 'pending':   return '<span class="fp-up-pill is-pending">Telegram…</span>';
            case 'uploaded':  return '<span class="fp-up-pill is-ok">Готово</span>';
            case 'failed':    return '<span class="fp-up-pill is-fail">Помилка</span>';
            default:          return '';
        }
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
        if (!summaryEl) return;
        const total = items.length;
        const done = items.filter(i => i.status === 'uploaded').length;
        const failed = items.filter(i => i.status === 'failed').length;
        const active = items.filter(i => i.status === 'uploading' || i.status === 'pending').length;
        const queued = items.filter(i => i.status === 'queued').length;

        let line;
        if (active + queued > 0) {
            line = `Завантаження: ${done + failed} з ${total}`;
        } else if (failed > 0) {
            line = `Готово: ${done} · з помилкою: ${failed}`;
        } else {
            line = `Усі ${total} файлів завантажено`;
        }
        summaryEl.textContent = line;

        widget.dataset.state = (active + queued > 0) ? 'active' : (failed > 0 ? 'with-failures' : 'done');
    }

    function showWidget() {
        widget.hidden = false;
        widget.classList.remove('is-collapsed');
    }

    function clearList() {
        items = [];
        listEl.innerHTML = '';
        widget.hidden = true;
        widget.classList.remove('is-collapsed');
    }

    /* ----------------------------------------------------
       Upload one file via XHR
       ---------------------------------------------------- */
    function uploadOne(item, formExtras) {
        return new Promise((resolve) => {
            const fd = new FormData();
            fd.append('files[]', item.file);
            if (formExtras.folder_id) fd.append('folder_id', formExtras.folder_id);
            if (formExtras.telegram_storage_group_id) fd.append('telegram_storage_group_id', formExtras.telegram_storage_group_id);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', endpoint, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);

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
                            item.error = 'Завантаження в Telegram не вдалося';
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
                    item.status = 'failed';
                    item.error = 'Перевищено ліміт запитів. Спробуйте пізніше.';
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
        if (!item.serverId) return;
        const url = statusUrlTpl.replace('__id__', String(item.serverId));
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
            } catch (_) {
                // network glitch; just retry
            }

            if (attempts < POLL_MAX_ATTEMPTS) {
                setTimeout(tick, POLL_INTERVAL_MS);
            } else {
                // give up — leave status as pending; user can refresh later
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
            const next = items.find(i => i.status === 'queued');
            if (!next) return;
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
    }

    function finishedAllUploads() {
        // If any are still pending (polling), keep wake lock until they all resolve
        const pending = items.some(i => i.status === 'pending');
        if (!pending) {
            releaseWakeLock();
            // refresh file list after a short delay if everything went OK
            if (reloadUrl && items.every(i => i.status === 'uploaded')) {
                setTimeout(() => { window.location.href = reloadUrl; }, 1200);
            }
        } else {
            // poll until pending resolved, then maybe reload
            const watcher = setInterval(() => {
                if (!items.some(i => i.status === 'pending')) {
                    clearInterval(watcher);
                    releaseWakeLock();
                    if (reloadUrl && items.every(i => i.status === 'uploaded')) {
                        setTimeout(() => { window.location.href = reloadUrl; }, 1200);
                    }
                }
            }, 1500);
        }
    }

    /* ----------------------------------------------------
       Wire up: intercept form submit
       ---------------------------------------------------- */
    form.addEventListener('submit', (e) => {
        const fileInput = form.querySelector('input[type="file"][name="files[]"]');
        if (!fileInput || !fileInput.files || !fileInput.files.length) return;

        e.preventDefault();

        const folderId = form.querySelector('[name="folder_id"]')?.value || '';
        const groupId = form.querySelector('[name="telegram_storage_group_id"]')?.value || '';

        const newItems = Array.from(fileInput.files).map((file) => ({
            id: `i${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            file,
            status: 'queued',
            progress: 0,
            error: null,
            serverId: null,
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
       Widget controls
       ---------------------------------------------------- */
    minimizeBtn?.addEventListener('click', () => {
        widget.classList.toggle('is-collapsed');
    });

    closeBtn?.addEventListener('click', () => {
        if (hasActiveWork()) {
            const ok = confirm('Активні завантаження ще тривають. Якщо закрити віджет, прогрес буде втрачено. Закрити?');
            if (!ok) return;
        }
        releaseWakeLock();
        clearList();
    });

    // Warn user before leaving page if uploads in progress
    window.addEventListener('beforeunload', (e) => {
        if (hasActiveWork()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
