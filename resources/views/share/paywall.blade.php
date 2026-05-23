@extends('layouts.site')

@section('title', 'Платний доступ — '.$file->original_name)
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
                <p>Платний доступ до файла</p>
            </div>
        </a>

        <div class="nav-actions">
            @auth
                <a class="button secondary" href="{{ route('files.index') }}">Мої файли</a>
            @else
                <a class="button secondary" href="{{ route('login') }}">Увійти</a>
            @endauth
        </div>
    </header>

    <section class="panel paywall-shell">
        <div class="paywall-card">
            <span class="share-badge paywall-badge">Платний файл</span>
            <h1 class="paywall-title">{{ $file->original_name }}</h1>
            <p class="paywall-meta">{{ $file->mime_type ?? 'unknown' }} · {{ $file->human_size }}</p>

            <div class="paywall-price">
                <span class="paywall-price-value">{{ $file->price_formatted }}</span>
                <span class="paywall-price-note">одноразова оплата</span>
            </div>

            <ul class="paywall-benefits">
                <li>Миттєвий доступ після оплати</li>
                @if ($file->purchase_max_downloads)
                    <li>До {{ $file->purchase_max_downloads }} {{ $file->purchase_max_downloads === 1 ? 'завантаження' : 'завантажень' }}</li>
                @else
                    <li>Без обмежень на кількість завантажень</li>
                @endif
                @if ($file->purchase_access_hours)
                    <li>Доступ {{ $file->purchase_access_hours }} год. після оплати</li>
                @else
                    <li>Доступ без обмеження за часом</li>
                @endif
                <li>Безпечна оплата через Lemon Squeezy</li>
            </ul>

            @if ($errors->any())
                <div class="paywall-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('share.files.checkout', $shareToken) }}" class="paywall-form">
                @csrf
                <button class="button paywall-buy-button" type="submit">Купити за {{ $file->price_formatted }}</button>
            </form>

            <p class="paywall-footnote">
                Натискаючи кнопку, ви будете перенаправлені на сторінку оплати. Після успішної транзакції ви автоматично отримаєте доступ до файла.
            </p>
        </div>
    </section>
@endsection
