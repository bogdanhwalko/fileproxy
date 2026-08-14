<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'FileProxy — безкоштовне сховище і керування файлами')</title>

    @php
        $metaDescription = trim((string) View::yieldContent(
            'description',
            'FileProxy — безкоштовний файловий кабінет з папками, пошуком і публічними лінками. Telegram-сховище, перегляд у браузері, обмеження доступу за переглядами і датою.'
        ));
        $metaKeywords = trim((string) View::yieldContent(
            'keywords',
            'FileProxy, файлове сховище, telegram сховище, безкоштовне сховище файлів, файловий менеджер, обмін файлами, публічні посилання, керування файлами'
        ));
        $canonicalUrl = url()->current();
        $ogTitle = trim((string) View::yieldContent('og_title', 'FileProxy — безкоштовне сховище і керування файлами'));
        $ogImage = asset(View::yieldContent('og_image') ?: 'favicon2.ico');
        $robotsContent = trim((string) View::yieldContent('robots', 'index, follow'));
    @endphp

    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords" content="{{ $metaKeywords }}">
    <meta name="robots" content="{{ $robotsContent }}">
    <meta name="googlebot" content="{{ $robotsContent }}">
    <meta name="theme-color" content="#4f46e5">
    <meta name="application-name" content="FileProxy">
    <meta name="author" content="FileProxy">

    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="icon" href="{{ asset('favicon2.ico') }}" type="image/x-icon">
    <link rel="alternate" hreflang="uk" href="{{ url('/') }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/uk.js" defer></script>

    <meta property="og:type" content="website">
    <meta property="og:locale" content="uk_UA">
    <meta property="og:site_name" content="FileProxy">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="stylesheet" href="@vasset('css/site.css')">

    @stack('head')
</head>
<body>
    <main class="page">
        @yield('content')
    </main>
    @include('partials.command-palette')
    <script>
        (() => {
            const maxLocalLength = 13;
            const minTotalDigits = 8;
            const maxTotalDigits = 15;

            const onlyDigits = (value) => (value || '').replace(/\D/g, '');

            const localDigits = (value) => {
                let digits = onlyDigits(value);

                if (digits.startsWith('0')) {
                    digits = digits.slice(1);
                }

                return digits.slice(0, maxLocalLength);
            };

            document.querySelectorAll('[data-phone-mask]').forEach((wrapper) => {
                const fieldGroup = wrapper.closest('.field-group') || document;
                const fullInput = fieldGroup.querySelector('[data-phone-full]');
                const localInput = wrapper.querySelector('[data-phone-local]');
                const countrySelect = wrapper.querySelector('[data-phone-country]');
                const flagDisplay = wrapper.querySelector('[data-phone-country-flag]');
                const codeDisplay = wrapper.querySelector('[data-phone-country-code]');

                if (! fullInput || ! localInput || ! countrySelect) {
                    return;
                }

                // On validation-error round-trip, the hidden field still holds the
                // previously submitted full number — split it back into country + local
                // so the select and visible input reflect what the user typed.
                const existingDigits = onlyDigits(fullInput.value);

                if (existingDigits) {
                    const matchedOption = Array.from(countrySelect.options)
                        .sort((a, b) => onlyDigits(b.value).length - onlyDigits(a.value).length)
                        .find((option) => existingDigits.startsWith(onlyDigits(option.value)));

                    if (matchedOption) {
                        countrySelect.value = matchedOption.value;
                        localInput.value = existingDigits.slice(onlyDigits(matchedOption.value).length);
                    }
                }

                // The visible box only ever shows "flag + dial code"; the full
                // country name stays in the native <select> list (see the CSS
                // comment on .phone-country-picker for why it's built this way).
                const updateCountryDisplay = () => {
                    const option = countrySelect.options[countrySelect.selectedIndex];

                    if (flagDisplay) {
                        flagDisplay.textContent = option?.dataset.flag || '';
                    }

                    if (codeDisplay) {
                        codeDisplay.textContent = countrySelect.value;
                    }
                };

                const sync = () => {
                    const prefixDigits = onlyDigits(countrySelect.value);
                    const digits = localDigits(localInput.value);
                    const totalDigits = prefixDigits.length + digits.length;

                    localInput.value = digits;
                    fullInput.value = totalDigits >= minTotalDigits && totalDigits <= maxTotalDigits
                        ? '+' + prefixDigits + digits
                        : '';

                    updateCountryDisplay();
                };

                sync();

                localInput.addEventListener('input', sync);
                countrySelect.addEventListener('change', sync);
                localInput.form?.addEventListener('submit', sync);
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
