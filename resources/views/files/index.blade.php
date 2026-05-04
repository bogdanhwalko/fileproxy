@extends('layouts.site')

@section('title', 'Файли - FileProxy')

@section('content')
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Завантаження, папки і швидке керування файлами</p>
            </div>
        </a>

        <div class="nav-actions">
            <span class="user-chip">{{ auth()->user()->name }}</span>
            @if (auth()->user()->is_admin)
                <a class="button secondary" href="{{ route('admin.users.index') }}">Адмінка</a>
            @endif
            <a class="button secondary" href="{{ route('telegram-settings.index') }}">Telegram-сховище</a>
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button class="button secondary" type="submit">Вийти</button>
            </form>
        </div>
    </header>

    <div data-flash-area>
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

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
    </div>

    <section class="stats" aria-label="Статистика сховища">
        <div class="stat">
            <span>Усього файлів</span>
            <strong>{{ $stats['total'] }}</strong>
        </div>
        <div class="stat">
            <span>Зайнято місця</span>
            <strong>{{ $stats['storage'] }}</strong>
        </div>
        <div class="stat">
            <span>Папки</span>
            <strong>{{ $stats['folders'] }}</strong>
        </div>
        <div class="stat">
            <span>У Telegram</span>
            <strong>{{ $stats['telegram'] }}</strong>
        </div>
        <div class="stat">
            <span>У поточному розділі</span>
            <strong>{{ $stats['current'] }}</strong>
        </div>
    </section>

    <section class="panel upload-panel">
        <div class="upload-panel-head">
            <div>
                <span class="section-kicker">Нове завантаження</span>
                <h2>Додайте файли у вибране сховище</h2>
                <p>Підтримується багато файлів за раз. Один файл - до {{ $telegramUploadMaxMb }} MB.</p>
            </div>
            <div class="upload-limit">
                <span>Ліміт файла</span>
                <strong>{{ $telegramUploadMaxMb }} MB</strong>
            </div>
        </div>

        <form class="upload-form premium-upload-form" action="{{ route('files.store') }}" method="post" enctype="multipart/form-data" data-upload-form>
            @csrf
            <div class="upload-fields">
                <div class="field-group upload-target">
                    <label for="folder_id">Папка</label>
                    <select class="field" id="folder_id" name="folder_id">
                        <option value="">Без папки</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}" @selected($activeFolder?->id === $folder->id)>{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field-group upload-target">
                    <label for="telegram_storage_group_id">Сховище</label>
                    <select class="field" id="telegram_storage_group_id" name="telegram_storage_group_id">
                        @if ($canUseLocalStorage)
                            <option value="">Локальне сховище</option>
                        @elseif ($telegramStorageGroups->isEmpty() && $systemTelegramStorageAvailable)
                            <option value="">Системне Telegram-сховище</option>
                        @elseif ($telegramStorageGroups->isEmpty())
                            <option value="">Telegram-сховище не налаштоване</option>
                        @else
                            <option value="">Оберіть Telegram-групу</option>
                        @endif
                        @foreach ($telegramStorageGroups as $storageGroup)
                            <option value="{{ $storageGroup->id }}" @selected((string) old('telegram_storage_group_id', $storageGroup->is_default ? $storageGroup->id : '') === (string) $storageGroup->id)>
                                Telegram: {{ $storageGroup->title }} · {{ $storageGroup->botToken?->name ?? 'бот' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if (! $canUseLocalStorage && $telegramStorageGroups->isEmpty())
                    <div class="upload-meta">
                        @if ($systemTelegramStorageAvailable)
                            Буде використано системне Telegram-сховище адміністратора. Залишилось {{ $systemTelegramRemainingUploads }} з {{ $systemTelegramUploadLimit }} файлів.
                        @else
                            Власну Telegram-групу ще не підключено або системний ліміт вичерпано. Додайте власного бота і групу в налаштуваннях.
                        @endif
                    </div>
                @endif
            </div>

            <label class="dropzone">
                <span class="dropzone-inner">
                    <span class="dropzone-icon">+</span>
                    <span class="dropzone-copy">
                        <strong>Оберіть файли для завантаження</strong>
                        <span>
                            @if ($canUseLocalStorage)
                                Локально або Telegram, залежно від обраного сховища.
                            @else
                                Для звичайних користувачів доступне тільки Telegram-сховище.
                            @endif
                        </span>
                    </span>
                    <input type="file" name="files[]" multiple required>
                </span>
            </label>

            <div class="upload-progress" data-upload-progress hidden>
                <div class="upload-progress-head">
                    <strong data-upload-progress-label>Підготовка до завантаження...</strong>
                    <span data-upload-progress-percent>0%</span>
                </div>
                <div class="upload-progress-track" aria-hidden="true">
                    <span data-upload-progress-bar style="width: 0%"></span>
                </div>
                <p data-upload-progress-note>Прогрес приблизний: після передачі файлів сервер ще може обробляти Telegram-сховище.</p>
            </div>

            <div class="upload-footer">
                <div class="upload-meta">
                    Метадані зберігаються в MariaDB, а файли - у дозволеному сховищі.
                </div>
                <div class="upload-actions">
                    <button class="button" type="submit">Завантажити</button>
                    @if (! $telegramStorageGroups->count() && ! $systemTelegramStorageAvailable)
                        <a class="button secondary" href="{{ route('telegram-settings.index') }}">Як прив’язати Telegram</a>
                    @elseif (! $telegramStorageGroups->count())
                        <a class="button secondary" href="{{ route('telegram-settings.index') }}">Власне Telegram-сховище</a>
                    @endif
                </div>
            </div>
        </form>
    </section>

    <section class="workspace">
        <aside class="sidebar-stack">
            <section class="panel">
                <div class="panel-header">
                    <h2>Папки</h2>
                    <p>Створюйте окремі папки і завантажуйте файли безпосередньо в потрібний розділ.</p>
                </div>

                <form class="folder-form" action="{{ route('folders.store') }}" method="post" data-ajax-form>
                    @csrf
                    <input class="field" type="text" name="name" value="{{ old('name') }}" placeholder="Назва папки" maxlength="100" required>
                    <button class="button" type="submit">Створити</button>
                </form>

                <div class="folder-list" aria-label="Список папок">
                    <a class="folder-link {{ $folderFilter === 'all' ? 'active' : '' }}" href="{{ route('files.index') }}">
                        <span class="folder-name">Усі файли</span>
                        <span class="folder-count">{{ $stats['total'] }}</span>
                    </a>
                    <a class="folder-link {{ $folderFilter === 'root' ? 'active' : '' }}" href="{{ route('files.index', ['folder' => 'root']) }}">
                        <span class="folder-name">Без папки</span>
                        <span class="folder-count">{{ $stats['root'] }}</span>
                    </a>

                    @foreach ($folders as $folder)
                        <div class="folder-row">
                            <a class="folder-link {{ $activeFolder?->id === $folder->id ? 'active' : '' }}" href="{{ route('files.index', ['folder' => $folder->id]) }}">
                                <span class="folder-name">{{ $folder->name }}</span>
                                <span class="folder-count">{{ $folder->files_count }}</span>
                            </a>
                            <div class="folder-actions">
                                @include('files.partials.folder-actions', ['folder' => $folder])
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </aside>

        <section class="panel" aria-label="Список файлів">
            <form class="filters" action="{{ route('files.index') }}" method="get" data-ajax-filter>
                @if ($folderFilter !== 'all')
                    <input type="hidden" name="folder" value="{{ $folderFilter }}">
                @endif
                <input type="hidden" name="view" value="{{ $display }}">
                <input class="field" type="search" name="search" value="{{ $search }}" placeholder="Пошук за назвою, MIME або розширенням">
                <select class="field" name="type">
                    <option value="all" @selected($type === 'all')>Усі типи</option>
                    <option value="images" @selected($type === 'images')>Зображення</option>
                    <option value="documents" @selected($type === 'documents')>Документи</option>
                    <option value="archives" @selected($type === 'archives')>Архіви</option>
                </select>
                <button class="button secondary" type="submit">Фільтрувати</button>
            </form>

            <div class="file-view-bar">
                <span data-file-summary data-total="{{ $files->total() }}">Показано {{ $files->count() }} з {{ $files->total() }}</span>
                <div class="view-toggle" aria-label="Вигляд списку файлів">
                    <a class="button secondary {{ $display === 'table' ? 'active' : '' }}" href="{{ route('files.index', array_merge(request()->except(['page', 'view', 'image_previews']), ['view' => 'table'])) }}">Таблиця</a>
                    <a class="button secondary {{ $display === 'grid' ? 'active' : '' }}" href="{{ route('files.index', array_merge(request()->except(['page', 'view']), ['view' => 'grid'])) }}">Плитки</a>
                    @if ($display === 'grid')
                        <a
                            class="button secondary {{ $imagePreviews ? 'active' : '' }}"
                            href="{{ route('files.index', $imagePreviews
                                ? array_merge(request()->except(['page', 'image_previews']), ['view' => 'grid'])
                                : array_merge(request()->except(['page']), ['view' => 'grid', 'image_previews' => 1])) }}"
                        >
                            {{ $imagePreviews ? 'Фото увімкнено' : 'Передзавантажити фото' }}
                        </a>
                    @endif
                </div>
            </div>

            @if ($display === 'grid')
                <div class="file-grid {{ $imagePreviews ? 'with-previews' : '' }}" data-file-items>
                    @forelse ($files as $file)
                        <article class="file-tile" data-file-item>
                            @if ($imagePreviews && $file->is_image)
                                <a class="file-tile-preview" href="{{ route('files.preview', $file) }}" aria-label="Відкрити {{ $file->original_name }}">
                                    <img src="{{ route('files.inline', $file) }}" alt="{{ $file->original_name }}" loading="eager" decoding="async">
                                </a>
                            @elseif ($imagePreviews)
                                <div class="file-tile-preview file-tile-preview-empty" aria-hidden="true">
                                    <span>{{ $file->type_label }}</span>
                                </div>
                            @endif
                            <div class="file-tile-head">
                                <span class="file-icon">{{ $file->type_label }}</span>
                                <div class="file-tile-title">
                                    <strong title="{{ $file->original_name }}">{{ $file->original_name }}</strong>
                                    <span>{{ $file->mime_type ?? 'unknown' }}</span>
                                </div>
                            </div>
                            <div class="file-tile-meta">
                                <span>{{ $file->folder?->name ?? 'Без папки' }}</span>
                                <span>{{ $file->storage_label }}</span>
                                <span>{{ $file->human_size }} · {{ $file->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            <div class="file-tile-actions">
                                @include('files.partials.actions', ['file' => $file])
                            </div>
                        </article>
                    @empty
                        <div class="empty">У цьому розділі ще немає файлів. Додайте файл через форму завантаження.</div>
                    @endforelse
                </div>
            @else
                <div class="table-wrap">
                    <table class="compact-file-table">
                        <thead>
                            <tr>
                                <th>Файл</th>
                                <th>Папка</th>
                                <th>Сховище</th>
                                <th>Розмір</th>
                                <th>Дата</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody data-file-items>
                            @forelse ($files as $file)
                                <tr data-file-item>
                                    <td>
                                        <div class="file-table-name">
                                            <span class="file-icon">{{ $file->type_label }}</span>
                                            <div class="file-table-title">
                                                <strong title="{{ $file->original_name }}">{{ $file->original_name }}</strong>
                                                <span>{{ $file->mime_type ?? 'unknown' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="muted">{{ $file->folder?->name ?? 'Без папки' }}</td>
                                    <td class="muted">{{ $file->storage_label }}</td>
                                    <td>{{ $file->human_size }}</td>
                                    <td class="muted">{{ $file->created_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        <div class="file-row-actions">
                                            @include('files.partials.actions', ['file' => $file])
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty">У цьому розділі ще немає файлів. Додайте файл через форму завантаження.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($files->hasMorePages())
                <nav class="pagination" data-load-more aria-label="Завантаження додаткових файлів">
                    <span>Показано {{ min($files->currentPage() * $files->perPage(), $files->total()) }} з {{ $files->total() }}</span>
                    <a class="button secondary" href="{{ $files->nextPageUrl() }}" data-load-more-link>Завантажити ще</a>
                </nav>
            @endif
        </section>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            if (! document.querySelector('[data-file-items]')) {
                return;
            }

            window.addEventListener('popstate', () => {
                refreshFilesPage(window.location.href, false);
            });

            document.addEventListener('submit', async (event) => {
                const uploadForm = event.target.closest('[data-upload-form]');

                if (uploadForm) {
                    event.preventDefault();
                    uploadFiles(uploadForm);

                    return;
                }

                const filterForm = event.target.closest('[data-ajax-filter]');

                if (filterForm) {
                    event.preventDefault();

                    const url = new URL(filterForm.action, window.location.origin);
                    new FormData(filterForm).forEach((value, key) => {
                        if (value !== '') {
                            url.searchParams.set(key, value);
                        }
                    });
                    url.searchParams.delete('page');

                    refreshFilesPage(url.toString(), true);

                    return;
                }

                const ajaxForm = event.target.closest('[data-ajax-form]');

                if (ajaxForm) {
                    event.preventDefault();
                    submitAjaxForm(ajaxForm);
                }
            });

            document.addEventListener('click', async (event) => {
                const actionTrigger = event.target.closest('.action-menu-trigger');

                if (actionTrigger) {
                    const currentMenu = actionTrigger.closest('[data-file-share]');

                    document.querySelectorAll('[data-file-share][open]').forEach((menu) => {
                        if (menu !== currentMenu) {
                            menu.removeAttribute('open');
                        }
                    });

                    requestAnimationFrame(() => {
                        if (currentMenu?.open) {
                            positionActionPanel(currentMenu, actionTrigger);
                        }
                    });

                    return;
                }

                const actionClose = event.target.closest('[data-action-close]');

                if (actionClose) {
                    event.preventDefault();
                    actionClose.closest('[data-file-share]')?.removeAttribute('open');

                    return;
                }

                const shareEnable = event.target.closest('[data-share-enable]');
                const shareDisable = event.target.closest('[data-share-disable]');
                const shareSave = event.target.closest('[data-share-save]');
                const shareCopy = event.target.closest('[data-share-copy]');

                if (shareEnable || shareDisable || shareSave || shareCopy) {
                    const panel = event.target.closest('[data-file-share]');

                    if (! panel) {
                        return;
                    }

                    event.preventDefault();

                    if (shareCopy) {
                        copyShareLink(panel);
                        return;
                    }

                    try {
                        const activeButton = shareEnable || shareDisable || shareSave;
                        setShareBusy(activeButton, true);

                        if (shareEnable) {
                            const data = await sendShareRequest(panel.dataset.shareUrl, 'POST');
                            updateSharePanel(panel, data.share, data.message);
                        } else if (shareDisable) {
                            const data = await sendShareRequest(panel.dataset.shareDisableUrl, 'DELETE');
                            updateSharePanel(panel, data.share, data.message);
                        } else if (shareSave) {
                            const data = await sendShareRequest(panel.dataset.shareSettingsUrl, 'PATCH', {
                                share_max_views: panel.querySelector('[data-share-max-views]')?.value || null,
                                share_expires_at: panel.querySelector('[data-share-expires-at]')?.value || null,
                            });
                            updateSharePanel(panel, data.share, data.message);
                        }
                    } catch (error) {
                        showShareMessage(panel, error.message || 'Не вдалося зберегти налаштування.', true);
                    } finally {
                        setShareBusy(shareEnable || shareDisable || shareSave, false);
                    }

                    return;
                }

                if (! event.target.closest('[data-file-share]')) {
                    document.querySelectorAll('[data-file-share][open]').forEach((menu) => {
                        menu.removeAttribute('open');
                    });
                }

                const link = event.target.closest('[data-load-more-link]');

                if (link) {
                    event.preventDefault();
                    loadMoreFiles(link);

                    return;
                }

                const navigationLink = event.target.closest('.folder-link, .view-toggle a');

                if (navigationLink && ! navigationLink.target) {
                    event.preventDefault();
                    refreshFilesPage(navigationLink.href, true);
                }
            });

            document.addEventListener('pointerdown', (event) => {
                const handle = event.target.closest('[data-action-drag-handle]');

                if (! handle || event.target.closest('[data-action-close]')) {
                    return;
                }

                const panel = handle.closest('.file-action-panel');

                if (! panel) {
                    return;
                }

                event.preventDefault();

                const rect = panel.getBoundingClientRect();
                const offsetX = event.clientX - rect.left;
                const offsetY = event.clientY - rect.top;

                const movePanel = (moveEvent) => {
                    moveEvent.preventDefault();

                    setPanelPosition(panel, moveEvent.clientX - offsetX, moveEvent.clientY - offsetY);
                };

                const stopDragging = () => {
                    document.removeEventListener('pointermove', movePanel);
                    document.removeEventListener('pointerup', stopDragging);
                    document.removeEventListener('pointercancel', stopDragging);
                };

                document.addEventListener('pointermove', movePanel);
                document.addEventListener('pointerup', stopDragging, { once: true });
                document.addEventListener('pointercancel', stopDragging, { once: true });
            });

            async function submitAjaxForm(form) {
                const submitter = form.querySelector('[type="submit"]');

                try {
                    setShareBusy(submitter, true);

                    const response = await fetch(form.action, {
                        method: (form.method || 'POST').toUpperCase(),
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                    });
                    const html = await response.text();

                    if (! response.ok) {
                        throw new Error(extractErrorFromHtml(html) || 'Дію не виконано.');
                    }

                    replaceFilesPageFromHtml(html, response.url || window.location.href, true);
                } catch (error) {
                    showPageFlash(error.message || 'Дію не виконано.', true);
                } finally {
                    setShareBusy(submitter, false);
                }
            }

            function uploadFiles(form) {
                const fileInput = form.querySelector('input[type="file"]');

                if (! fileInput?.files?.length) {
                    showPageFlash('Оберіть хоча б один файл для завантаження.', true);
                    return;
                }

                const submitter = form.querySelector('[type="submit"]');
                const xhr = new XMLHttpRequest();
                let fallbackProgress = 0;
                const fallbackTimer = window.setInterval(() => {
                    fallbackProgress = Math.min(fallbackProgress + 3, 72);
                    setUploadProgress(form, fallbackProgress, 'Завантаження файлів...');
                }, 650);

                setShareBusy(submitter, true);
                setUploadProgress(form, 2, 'Підготовка до завантаження...');

                xhr.open('POST', form.action);
                xhr.setRequestHeader('Accept', 'text/html');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.addEventListener('progress', (event) => {
                    if (! event.lengthComputable) {
                        return;
                    }

                    const percent = Math.min(85, Math.max(2, Math.round((event.loaded / event.total) * 85)));
                    fallbackProgress = percent;
                    setUploadProgress(form, percent, 'Передача файлів на сервер...');
                });

                xhr.upload.addEventListener('load', () => {
                    fallbackProgress = Math.max(fallbackProgress, 90);
                    setUploadProgress(form, fallbackProgress, 'Файли передано. Обробка сховища...');
                });

                xhr.onload = () => {
                    window.clearInterval(fallbackTimer);
                    setShareBusy(submitter, false);

                    if (xhr.status < 200 || xhr.status >= 300) {
                        setUploadProgress(form, 0, 'Завантаження не виконано.');
                        showPageFlash(extractErrorFromHtml(xhr.responseText) || 'Завантаження не виконано.', true);
                        return;
                    }

                    setUploadProgress(form, 100, 'Готово. Оновлюю список файлів...');

                    window.setTimeout(() => {
                        replaceFilesPageFromHtml(xhr.responseText, xhr.responseURL || window.location.href, true);
                    }, 220);
                };

                xhr.onerror = () => {
                    window.clearInterval(fallbackTimer);
                    setShareBusy(submitter, false);
                    setUploadProgress(form, 0, 'Помилка мережі.');
                    showPageFlash('Не вдалося передати файли. Перевірте з’єднання і повторіть спробу.', true);
                };

                xhr.send(new FormData(form));
            }

            async function refreshFilesPage(url, pushHistory) {
                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const html = await response.text();

                    if (! response.ok) {
                        throw new Error(extractErrorFromHtml(html) || 'Не вдалося оновити сторінку.');
                    }

                    replaceFilesPageFromHtml(html, response.url || url, pushHistory);
                } catch (error) {
                    showPageFlash(error.message || 'Не вдалося оновити сторінку.', true);
                }
            }

            async function loadMoreFiles(link) {
                const currentItems = document.querySelector('[data-file-items]');

                if (! currentItems) {
                    window.location.href = link.href;
                    return;
                }

                const originalText = link.textContent;
                link.textContent = 'Завантаження...';
                link.setAttribute('aria-disabled', 'true');

                try {
                    const response = await fetch(link.href, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nextItems = doc.querySelector('[data-file-items]');

                    if (! response.ok || ! nextItems) {
                        throw new Error(extractErrorFromHtml(html) || 'Не вдалося завантажити наступну сторінку.');
                    }

                    Array.from(nextItems.children).forEach((item) => currentItems.appendChild(item));

                    const currentPagination = document.querySelector('[data-load-more]');
                    const nextPagination = doc.querySelector('[data-load-more]');

                    if (currentPagination && nextPagination) {
                        currentPagination.replaceWith(nextPagination);
                    } else if (currentPagination) {
                        currentPagination.remove();
                    }

                    const summary = document.querySelector('[data-file-summary]');

                    if (summary) {
                        summary.textContent = `Показано ${currentItems.querySelectorAll('[data-file-item]').length} з ${summary.dataset.total}`;
                    }
                } catch (error) {
                    link.textContent = originalText;
                    link.removeAttribute('aria-disabled');
                    showPageFlash(error.message || 'Не вдалося завантажити файли.', true);
                }
            }

            function replaceFilesPageFromHtml(html, url, pushHistory) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const selectors = [
                    '[data-flash-area]',
                    '.stats',
                    '.upload-panel',
                    '.workspace',
                ];

                selectors.forEach((selector) => {
                    const current = document.querySelector(selector);
                    const next = doc.querySelector(selector);

                    if (current && next) {
                        current.replaceWith(next);
                    }
                });

                document.querySelectorAll('[data-file-share][open]').forEach((menu) => {
                    menu.removeAttribute('open');
                });

                if (pushHistory && url && url !== window.location.href) {
                    window.history.pushState({}, '', url);
                }
            }

            function setUploadProgress(form, percent, label) {
                const progress = form.querySelector('[data-upload-progress]');

                if (! progress) {
                    return;
                }

                const normalized = Math.max(0, Math.min(100, Math.round(percent)));
                const bar = progress.querySelector('[data-upload-progress-bar]');
                const percentLabel = progress.querySelector('[data-upload-progress-percent]');
                const textLabel = progress.querySelector('[data-upload-progress-label]');

                progress.hidden = false;

                if (bar) {
                    bar.style.width = `${normalized}%`;
                }

                if (percentLabel) {
                    percentLabel.textContent = `${normalized}%`;
                }

                if (textLabel) {
                    textLabel.textContent = label;
                }
            }

            function extractErrorFromHtml(html) {
                const doc = new DOMParser().parseFromString(html || '', 'text/html');
                const errorItems = Array.from(doc.querySelectorAll('.errors li')).map((item) => item.textContent.trim());

                return errorItems[0] || doc.querySelector('.errors')?.textContent.trim() || '';
            }

            function showPageFlash(message, isError) {
                let flash = document.querySelector('[data-flash-area]');

                if (! flash) {
                    flash = document.createElement('div');
                    flash.dataset.flashArea = '';
                    document.querySelector('.topbar')?.after(flash);
                }

                flash.innerHTML = isError
                    ? `<div class="errors"><strong>Перевірте дію.</strong><ul><li>${escapeHtml(message)}</li></ul></div>`
                    : `<div class="status">${escapeHtml(message)}</div>`;
            }

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value;

                return div.innerHTML;
            }

            async function sendShareRequest(url, method, payload = null) {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: payload ? JSON.stringify(payload) : null,
                });
                const data = await response.json().catch(() => ({}));

                if (! response.ok) {
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(firstError || data.message || 'Запит не виконано.');
                }

                return data;
            }

            function positionActionPanel(menu, trigger) {
                const panel = menu.querySelector('.file-action-panel');

                if (! panel || ! trigger) {
                    return;
                }

                panel.style.removeProperty('--action-panel-left');
                panel.style.removeProperty('--action-panel-top');

                const triggerRect = trigger.getBoundingClientRect();
                const panelRect = panel.getBoundingClientRect();
                const gap = 8;
                let left = triggerRect.right - panelRect.width;
                let top = triggerRect.bottom + gap;

                if (top + panelRect.height > window.innerHeight - gap) {
                    top = triggerRect.top - panelRect.height - gap;
                }

                setPanelPosition(panel, left, top);
            }

            function setPanelPosition(panel, left, top) {
                const margin = 12;
                const rect = panel.getBoundingClientRect();
                const width = rect.width || Math.min(390, window.innerWidth - margin * 2);
                const height = rect.height || Math.min(560, window.innerHeight - margin * 2);
                const maxLeft = Math.max(margin, window.innerWidth - width - margin);
                const maxTop = Math.max(margin, window.innerHeight - height - margin);

                panel.style.setProperty('--action-panel-left', `${Math.min(Math.max(left, margin), maxLeft)}px`);
                panel.style.setProperty('--action-panel-top', `${Math.min(Math.max(top, margin), maxTop)}px`);
            }

            function updateSharePanel(panel, share, message) {
                const enabled = Boolean(share?.is_enabled);
                const enabledBlock = panel.querySelector('[data-share-enabled]');
                const disabledBlock = panel.querySelector('[data-share-disabled]');
                const status = panel.querySelector('[data-share-status]');
                const linkInput = panel.querySelector('[data-share-link-input]');
                const openLink = panel.querySelector('[data-share-open]');
                const maxViews = panel.querySelector('[data-share-max-views]');
                const expiresAt = panel.querySelector('[data-share-expires-at]');
                const usage = panel.querySelector('[data-share-usage]');

                panel.querySelector('.share-settings')?.classList.toggle('is-enabled', enabled);

                if (enabledBlock) {
                    enabledBlock.hidden = ! enabled;
                }

                if (disabledBlock) {
                    disabledBlock.hidden = enabled;
                }

                if (status) {
                    status.textContent = share?.status_label || (enabled ? 'Активний' : 'Вимкнено');
                }

                if (linkInput) {
                    linkInput.value = share?.url || '';
                }

                if (openLink) {
                    openLink.href = share?.url || '#';
                    openLink.toggleAttribute('aria-disabled', ! share?.url);
                }

                if (maxViews) {
                    maxViews.value = share?.share_max_views ?? '';
                }

                if (expiresAt) {
                    expiresAt.value = share?.share_expires_at_input || '';
                }

                if (usage) {
                    usage.textContent = share?.usage_label || 'Переглядів: 0 / без ліміту · Доступний до: без дати';
                }

                showShareMessage(panel, message || 'Збережено.', false);
            }

            function copyShareLink(panel) {
                const input = panel.querySelector('[data-share-link-input]');

                if (! input?.value) {
                    showShareMessage(panel, 'Спочатку створіть публічний лінк.', true);
                    return;
                }

                if (navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText(input.value)
                        .then(() => showShareMessage(panel, 'Лінк скопійовано.', false))
                        .catch(() => fallbackCopy(input, panel));
                    return;
                }

                fallbackCopy(input, panel);
            }

            function fallbackCopy(input, panel) {
                input.focus();
                input.select();
                document.execCommand('copy');
                showShareMessage(panel, 'Лінк скопійовано.', false);
            }

            function showShareMessage(panel, message, isError) {
                const target = panel.querySelector('[data-share-message]');

                if (! target) {
                    return;
                }

                target.textContent = message;
                target.classList.toggle('is-error', isError);
            }

            function setShareBusy(target, isBusy) {
                if (! target) {
                    return;
                }

                target.toggleAttribute('disabled', isBusy);
                target.setAttribute('aria-busy', isBusy ? 'true' : 'false');
            }
        })();
    </script>
@endpush
