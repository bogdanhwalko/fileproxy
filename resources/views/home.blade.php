@extends('layouts.site')

@section('title', 'FileProxy - файлове сховище')

@section('content')
    <nav class="site-nav">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Приватне файлове сховище на Laravel</p>
            </div>
        </a>

        <div class="nav-actions">
            @auth
                <a class="button secondary" href="{{ route('files.index') }}">Кабінет</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="button secondary" type="submit">Вийти</button>
                </form>
            @else
                <a class="button secondary" href="{{ route('login') }}">Увійти</a>
                <a class="button" href="{{ route('register') }}">Створити акаунт</a>
            @endauth
        </div>
    </nav>

    <section class="welcome-hero">
        <div class="welcome-copy">
            <p class="eyebrow">Laravel + Docker + MariaDB + Telegram</p>
            <h1>Керування файлами без зайвого інтерфейсного шуму</h1>
            <p>
                FileProxy поєднує приватний кабінет, папки, Telegram-сховище та адміністративний контроль
                в одному простому шаблоні для розгортання в Docker.
            </p>

            <div class="hero-actions">
                @auth
                    <a class="button" href="{{ route('files.index') }}">Відкрити кабінет</a>
                    @if (auth()->user()->is_admin)
                        <a class="button secondary" href="{{ route('admin.users.index') }}">Адмінка</a>
                    @endif
                @else
                    <a class="button" href="{{ route('register') }}">Почати роботу</a>
                    <a class="button secondary" href="{{ route('login') }}">У мене є акаунт</a>
                @endauth
            </div>
        </div>

        <div class="welcome-summary" aria-label="Ключові можливості">
            <div>
                <span>Сховище</span>
                <strong>Telegram або локально</strong>
            </div>
            <div>
                <span>Доступ</span>
                <strong>Телефон і код</strong>
            </div>
            <div>
                <span>Керування</span>
                <strong>Папки, фільтри, перегляд</strong>
            </div>
        </div>
    </section>

    <section class="welcome-grid" aria-label="Можливості FileProxy">
        <article class="welcome-card">
            <span class="welcome-card-index">01</span>
            <strong>Приватний доступ</strong>
            <p>Кожен користувач бачить тільки власні файли, папки та прив'язані Telegram-групи.</p>
        </article>

        <article class="welcome-card">
            <span class="welcome-card-index">02</span>
            <strong>Telegram-сховище</strong>
            <p>Файли можуть зберігатися в Telegram-групах з метаданими в MariaDB і тимчасовим завантаженням для перегляду.</p>
        </article>

        <article class="welcome-card">
            <span class="welcome-card-index">03</span>
            <strong>Адміністрування</strong>
            <p>Адмін керує користувачами, блокуванням, дефолтними групами та доступом до локального сховища.</p>
        </article>
    </section>
@endsection
