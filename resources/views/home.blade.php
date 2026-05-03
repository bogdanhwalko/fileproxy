@extends('layouts.site')

@section('title', 'FileProxy - файлове сховище')

@section('content')
    <nav class="site-nav">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Безкоштовне сховище для ваших файлів</p>
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
            <p class="eyebrow">Безкоштовне завантаження та порядок у файлах</p>
            <h1>Завантажуйте, зберігайте й знаходьте файли безкоштовно</h1>
            <p>
                FileProxy допомагає тримати документи, фото, архіви та робочі матеріали в одному
                приватному кабінеті. Додавайте файли, групуйте їх у папки, швидко шукайте потрібне
                і відкривайте доступ до своїх матеріалів з будь-якого пристрою.
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
                <span>Вартість</span>
                <strong>Безкоштовний старт</strong>
            </div>
            <div>
                <span>Порядок</span>
                <strong>Папки, пошук і фільтри</strong>
            </div>
            <div>
                <span>Контроль</span>
                <strong>Ваші файли тільки для вас</strong>
            </div>
        </div>
    </section>

    <section class="welcome-grid" aria-label="Можливості FileProxy">
        <article class="welcome-card">
            <span class="welcome-card-index">01</span>
            <strong>Безкоштовне завантаження</strong>
            <p>Додавайте важливі файли без складних налаштувань і зайвих платежів за базове користування.</p>
        </article>

        <article class="welcome-card">
            <span class="welcome-card-index">02</span>
            <strong>Зручне керування</strong>
            <p>Створюйте папки, фільтруйте список, переглядайте зображення й текстові файли без скачування.</p>
        </article>

        <article class="welcome-card">
            <span class="welcome-card-index">03</span>
            <strong>Приватний кабінет</strong>
            <p>Вхід за номером телефону й кодом допомагає швидко повернутися до файлів без запам'ятовування пароля.</p>
        </article>
    </section>
@endsection
