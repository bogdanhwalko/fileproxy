@extends('layouts.site')

@section('title', $file->original_name.' — FileProxy')
@section('robots', 'noindex, nofollow')

@section('content')
    @php
        $editorExtensions = ['txt','md','markdown','csv','log','json','yaml','yml','ini','conf','env','xml','sql','tex','html','htm','css','scss','sass','less','svg','js','mjs','ts','jsx','tsx','vue','php','py','rb','sh','bash','zsh','go','rs','java','kt','swift','c','cpp','cc','h','hpp','cs','r','lua','pl'];
        $canEditText = $file->is_text
            && in_array(strtolower((string) $file->extension), $editorExtensions, true)
            && ! $file->is_protected
            && ! ($file->folder?->is_password_protected);
    @endphp

    <x-app-topbar
        title="FileProxy"
        subtitle="Перегляд файла"
        :brandHref="route('files.index', $file->folder_id ? ['folder' => $file->folder_id] : [])"
    >
        @php
            $extLower = strtolower((string) $file->extension);
            $canEditDoc = $canEditText && in_array($extLower, ['html', 'htm'], true);
        @endphp
        @if ($canEditDoc)
            <a class="button secondary nav-button" href="{{ route('files.edit-doc', $file) }}" aria-label="Редагувати як документ">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="9" y1="13" x2="15" y2="13"/>
                    <line x1="9" y1="17" x2="13" y2="17"/>
                </svg>
                <span class="nav-button-label">Документ</span>
            </a>
        @endif
        @if ($canEditText)
            <a class="button secondary nav-button" href="{{ route('files.edit-text', $file) }}" aria-label="Редагувати як код">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                <span class="nav-button-label">{{ $canEditDoc ? 'Як код' : 'Редагувати' }}</span>
            </a>
        @endif
        <a class="button nav-button" href="{{ route('files.download', $file) }}" aria-label="Скачати">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            <span class="nav-button-label">Скачати</span>
        </a>
    </x-app-topbar>

    <section class="panel preview-shell">
        <div class="preview-toolbar">
            <div class="preview-title">
                <h1>{{ $file->original_name }}</h1>
                <p>{{ $file->mime_type ?? 'unknown' }} · {{ $file->human_size }} · {{ $file->folder?->name ?? 'Без папки' }} · {{ $file->storage_label }}</p>
                @if ($file->is_telegram)
                    <p>Chat ID: {{ $file->telegram_chat_id }} · Message ID: {{ $file->telegram_message_id }}</p>
                @endif
            </div>
        </div>

        <div class="preview-frame">
            @if ($file->is_protected && ! $protectedPreviewReady)
                <div class="preview-locked" data-protected-preview-locked data-preload-url="{{ route('files.preload', $file) }}">
                    <span class="preview-locked-icon" aria-hidden="true">🔒</span>
                    <p>Файл захищено паролем і зашифровано. Щоб переглянути, спершу розшифруйте його — вміст буде завантажено з Telegram і розшифровано на сервері.</p>
                    <button type="button" class="button" data-preload-trigger>
                        <span data-preload-label>Розшифрувати та переглянути</span>
                    </button>
                    <p class="preview-locked-error" data-preload-error hidden></p>
                </div>
            @elseif ($file->is_image)
                <img class="preview-image" src="{{ route('files.inline', $file) }}" alt="{{ $file->original_name }}">
            @elseif ($file->is_pdf)
                <object class="preview-pdf" data="{{ route('files.inline', $file) }}#view=FitH" type="application/pdf">
                    <p class="preview-pdf-fallback">
                        Браузер не підтримує вбудований перегляд PDF.
                        <a href="{{ route('files.inline', $file) }}" target="_blank" rel="noopener">Відкрити в новій вкладці</a>
                        або <a href="{{ route('files.download', $file) }}">скачати</a>.
                    </p>
                </object>
            @else
                @if ($isTruncated)
                    <p class="truncated-note">Показано перший 1 MB файла. Повну версію можна скачати.</p>
                @endif
                <pre class="text-preview">{{ $content }}</pre>
            @endif
        </div>
    </section>

    @if ($file->is_protected && ! $protectedPreviewReady)
        @push('scripts')
            <script>
                (() => {
                    const locked = document.querySelector('[data-protected-preview-locked]');
                    const trigger = locked?.querySelector('[data-preload-trigger]');
                    const label = locked?.querySelector('[data-preload-label]');
                    const errorEl = locked?.querySelector('[data-preload-error]');

                    if (! locked || ! trigger) {
                        return;
                    }

                    const originalLabel = label ? label.textContent : '';

                    trigger.addEventListener('click', async () => {
                        trigger.disabled = true;
                        if (errorEl) errorEl.hidden = true;
                        if (label) label.textContent = 'Розшифровуємо…';

                        try {
                            const response = await fetch(locked.dataset.preloadUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                    'Accept': 'application/json',
                                },
                            });

                            const data = await response.json().catch(() => null);

                            if (! response.ok || ! data?.ok) {
                                throw new Error(data?.message || 'Не вдалося розшифрувати файл.');
                            }

                            window.location.reload();
                        } catch (error) {
                            trigger.disabled = false;
                            if (label) label.textContent = originalLabel;
                            if (errorEl) {
                                errorEl.textContent = error.message || 'Сталася помилка. Спробуйте ще раз.';
                                errorEl.hidden = false;
                            }
                        }
                    });
                })();
            </script>
        @endpush
    @endif
@endsection
