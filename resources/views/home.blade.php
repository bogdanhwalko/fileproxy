@extends('layouts.site')

@section('title', 'FileProxy - безкоштовне керування файлами')

@section('content')
    <nav class="site-nav landing-nav">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Сховище файлів на основі Telegram</p>
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
                <a class="button" href="{{ route('register') }}">Почати безкоштовно</a>
            @endauth
        </div>
    </nav>

    <div class="mobile-cta-bar" aria-label="Швидка дія на мобільному">
        @auth
            <a class="button" href="{{ route('files.index') }}">Відкрити кабінет</a>
        @else
            <a class="button" href="{{ route('register') }}">Почати безкоштовно</a>
            <a class="button secondary" href="{{ route('login') }}">Увійти</a>
        @endauth
    </div>

    <section class="landing-panel">
        <div class="landing-hero-grid">
            <div class="landing-hero-copy">
                <h1>Зберігайте, знаходьте і діліться файлами без хаосу в чатах</h1>
                <p>
                    FileProxy дає особистий файловий кабінет з папками, пошуком, швидким переглядом і контрольованими
                    публічними лінками. Підходить для документів, фото, архівів, робочих матеріалів і файлів для клієнтів.
                </p>

                <div class="landing-points" aria-label="Ключові переваги">
                    <span>Безкоштовно</span>
                    <span>Безлімітно</span>
                    <span>Приватно</span>
                </div>

                <div class="hero-actions landing-actions">
                    @auth
                        <a class="button" href="{{ route('files.index') }}">Відкрити кабінет</a>
                        @if (auth()->user()->is_admin)
                            <a class="button secondary" href="{{ route('admin.users.index') }}">Адмінка</a>
                        @endif
                    @else
                        <a class="button" href="{{ route('register') }}">Створити акаунт</a>
                        <a class="button secondary" href="{{ route('login') }}">У мене вже є акаунт</a>
                    @endauth
                </div>
            </div>

            <div class="landing-snapshot" aria-label="Приклад робочого простору FileProxy">
                <div class="snapshot-toolbar">
                    <div>
                        <span class="snapshot-dot"></span>
                        <strong>Робочий простір</strong>
                    </div>
                    <span>Папки · Пошук · Доступ</span>
                </div>

                <div class="snapshot-body">
                    <aside class="snapshot-folders">
                        <strong>Папки</strong>
                        <span>Вхідні <b>18</b></span>
                        <span>Документи <b>42</b></span>
                        <span>Фото <b>96</b></span>
                        <span>Для клієнтів <b>12</b></span>
                    </aside>

                    <div class="snapshot-main">
                        <div class="snapshot-upload">
                            <span>+</span>
                            <div>
                                <strong>Завантажуйте одразу в потрібну папку</strong>
                                <small>Файли залишаються впорядкованими з першого дня</small>
                            </div>
                        </div>

                        <div class="snapshot-files">
                            <div>
                                <span class="file-icon">PDF</span>
                                <strong>Договір для підпису.pdf</strong>
                                <small>Лінк активний</small>
                            </div>
                            <div>
                                <span class="file-icon">JPG</span>
                                <strong>Фото товару.jpg</strong>
                                <small>Перегляд без скачування</small>
                            </div>
                            <div>
                                <span class="file-icon">ZIP</span>
                                <strong>Матеріали для клієнта.zip</strong>
                                <small>5 переглядів</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-section">

        <div class="landing-benefits">
            <article>
                <span>01</span>
                <strong>Безкоштовне завантаження</strong>
                <p>Починайте без оплати й підключайте власне сховище тоді, коли файлів стане більше.</p>
            </article>
            <article>
                <span>02</span>
                <strong>Папки замість хаосу</strong>
                <p>Розкладайте документи, фото, архіви та робочі матеріали по зрозумілих розділах.</p>
            </article>
            <article>
                <span>03</span>
                <strong>Швидкий пошук</strong>
                <p>Знаходьте потрібний файл за назвою, типом або папкою без ручного перегортання списків.</p>
            </article>
            <article>
                <span>04</span>
                <strong>Перегляд у браузері</strong>
                <p>Відкривайте зображення й текстові файли одразу, без зайвого скачування на пристрій.</p>
            </article>
            <article>
                <span>05</span>
                <strong>Контрольовані посилання</strong>
                <p>Діліться файлами або папками з обмеженням за кількістю переглядів чи датою доступу.</p>
            </article>
            <article>
                <span>06</span>
                <strong>Приватність</strong>
                <p>Зберігайте свої файли в приватному сховищі з контролем доступу.</p>
            </article>
        </div>
    </section>

    <section class="landing-final">
        <div>
            <span class="landing-label">Почніть зараз</span>
            <h2>Створіть безкоштовний кабінет і наведіть порядок у файлах уже сьогодні.</h2>
        </div>
        @auth
            <a class="button" href="{{ route('files.index') }}">Перейти до файлів</a>
        @else
            <a class="button" href="{{ route('register') }}">Почати безкоштовно</a>
        @endauth
    </section>
@endsection
