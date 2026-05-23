@extends('layouts.site')

@section('title', 'Обробка платежу — FileProxy')
@section('robots', 'noindex, nofollow')

@push('head')
    <link rel="stylesheet" href="@vasset('css/paywall.css')">
@endpush

@section('content')
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Обробка платежу</p>
            </div>
        </a>
    </header>

    <section class="panel paywall-shell">
        <div class="paywall-card paywall-processing">
            <div class="paywall-spinner" aria-hidden="true"></div>
            <h1 class="paywall-title">Обробляємо ваш платіж…</h1>
            <p class="paywall-meta">Це триває кілька секунд. Ми автоматично відкриємо файл, щойно платіж підтвердиться.</p>

            <a class="button secondary" href="{{ route('home') }}">На головну</a>

            <p class="paywall-footnote">
                Якщо ця сторінка не оновилась через 1 хвилину, перезавантажте її або зв'яжіться з нами із вказанням ID транзакції:
                <code>{{ $purchase->lemon_checkout_id ?? '—' }}</code>
            </p>
        </div>
    </section>

    <script>
        (function () {
            const url = @json(route('share.access.status', $purchase->access_token));
            const target = @json(route('share.access.show', $purchase->access_token));
            let attempts = 0;

            async function poll() {
                attempts++;

                try {
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (response.ok) {
                        const data = await response.json();
                        if (data.is_paid && data.access_url) {
                            window.location.replace(data.access_url || target);
                            return;
                        }
                    }
                } catch (e) {}

                if (attempts < 60) {
                    setTimeout(poll, 3000);
                }
            }

            setTimeout(poll, 2000);
        })();
    </script>
@endsection
