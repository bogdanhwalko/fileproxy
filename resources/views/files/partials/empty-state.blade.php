@php
    $hasActiveFilter = ($search ?? '') !== ''
        || ($type ?? 'all') !== 'all'
        || ($dateFrom ?? '') !== ''
        || ($dateTo ?? '') !== ''
        || ($activeTag ?? null) !== null;

    $isFolder = ($activeFolder ?? null) !== null;
    $isRoot   = ($folderFilter ?? '') === 'root';
    $resetUrl = route('files.index', $folderFilter !== 'all' && ! $hasActiveFilter
        ? ['folder' => $folderFilter]
        : []);
@endphp

<div class="fp-empty">
    <div class="fp-empty-graphic" aria-hidden="true">
        @if ($hasActiveFilter)
            <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="28" cy="28" r="16"/>
                <line x1="40" y1="40" x2="54" y2="54"/>
                <line x1="20" y1="28" x2="36" y2="28"/>
            </svg>
        @elseif ($isFolder)
            <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 18a4 4 0 0 1 4-4h12l5 6h23a4 4 0 0 1 4 4v22a4 4 0 0 1-4 4H12a4 4 0 0 1-4-4z"/>
                <line x1="32" y1="26" x2="32" y2="40"/>
                <line x1="25" y1="33" x2="39" y2="33"/>
            </svg>
        @else
            <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M48 24v28a4 4 0 0 1-4 4H20a4 4 0 0 1-4-4V12a4 4 0 0 1 4-4h16z"/>
                <polyline points="36 8 36 24 52 24"/>
                <line x1="32" y1="34" x2="32" y2="48"/>
                <line x1="25" y1="41" x2="39" y2="41"/>
            </svg>
        @endif
    </div>

    <h3 class="fp-empty-title">
        @if ($hasActiveFilter)
            Жодного файла під цей фільтр
        @elseif ($isFolder)
            У папці «{{ $activeFolder->name }}» поки порожньо
        @elseif ($isRoot)
            У корені сховища ще немає файлів
        @else
            Тут ще нічого немає
        @endif
    </h3>

    <p class="fp-empty-text">
        @if ($hasActiveFilter)
            Спробуйте інші слова, інший тип або інший період. Або скиньте фільтр і подивіться все.
        @elseif ($isFolder)
            Завантажте перший файл у форму вище — і обиріть цю папку як ціль.
        @else
            Перетягніть будь-який файл у форму вище, або натисніть «Обрати файли з пристрою».
        @endif
    </p>

    <div class="fp-empty-actions">
        @if ($hasActiveFilter)
            <a class="button" href="{{ $resetUrl }}">Скинути фільтр</a>
        @else
            <button type="button" class="button" onclick="document.querySelector('[data-upload-input]')?.click()">
                Обрати файли
            </button>
        @endif
    </div>
</div>
