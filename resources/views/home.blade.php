@extends('layouts.site')

@section('title', 'FileProxy - безкоштовне керування файлами')

@section('content')
    <nav class="site-nav landing-nav">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Файли, які легко знайти</p>
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

    <section class="landing-panel">
        <div class="landing-hero-copy">
            <span class="landing-label">Безкоштовне файлове сховище</span>
            <h1>Тримайте документи, фото й архіви в одному приватному кабінеті</h1>
            <p>
                Завантажуйте файли без зайвих кроків, розкладайте їх по папках, шукайте за назвою або типом
                і переглядайте зображення та текст прямо в браузері.
            </p>

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

        <div class="landing-metrics" aria-label="Переваги FileProxy">
            <div>
                <strong>0 грн</strong>
                <span>для старту</span>
            </div>
            <div>
                <strong>50 MB</strong>
                <span>на один файл</span>
            </div>
            <div>
                <strong>20</strong>
                <span>файлів на першій сторінці</span>
            </div>
            <div>
                <strong>24/7</strong>
                <span>доступ до кабінету</span>
            </div>
        </div>

        <div class="landing-snapshot" aria-label="Приклад інтерфейсу FileProxy">
            <div class="snapshot-toolbar">
                <div>
                    <span class="snapshot-dot"></span>
                    <strong>Моє сховище</strong>
                </div>
                <span>Папки · Пошук · Перегляд</span>
            </div>

            <div class="snapshot-body">
                <aside class="snapshot-folders">
                    <strong>Папки</strong>
                    <span>Документи <b>42</b></span>
                    <span>Особисте <b>18</b></span>
                    <span>Робота <b>68</b></span>
                </aside>

                <div class="snapshot-main">
                    <div class="snapshot-upload">
                        <span>+</span>
                        <div>
                            <strong>Швидке завантаження</strong>
                            <small>Оберіть файл і папку</small>
                        </div>
                    </div>

                    <div class="snapshot-files">
                        <div>
                            <span class="file-icon">PDF</span>
                            <strong>Квитанція за оплату.pdf</strong>
                            <small>Документи</small>
                        </div>
                        <div>
                            <span class="file-icon">JPG</span>
                            <strong>Фото документа.jpg</strong>
                            <small>Особисте</small>
                        </div>
                        <div>
                            <span class="file-icon">TXT</span>
                            <strong>Нотатки до проєкту.txt</strong>
                            <small>Робота</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-section">
        <div class="landing-section-head">
            <span class="landing-label">Навіщо це потрібно</span>
            <h2>Коли файлів стає багато, важлива не тільки пам'ять, а порядок.</h2>
        </div>

        <div class="landing-benefits">
            <article>
                <strong>Безкоштовно завантажуйте важливе</strong>
                <p>Зберігайте документи, фото, скани, архіви й робочі матеріали без складної підготовки.</p>
            </article>
            <article>
                <strong>Не губіть файли в чатах</strong>
                <p>Папки, фільтри та пошук допомагають швидко знайти потрібний файл навіть через місяці.</p>
            </article>
            <article>
                <strong>Відкривайте без скачування</strong>
                <p>Переглядайте зображення й текстові файли одразу в кабінеті, коли треба швидко перевірити вміст.</p>
            </article>
        </div>
    </section>

    <section class="landing-flow">
        <div>
            <span>1</span>
            <strong>Створіть кабінет</strong>
            <p>Вхід за телефоном і кодом без потреби пам'ятати пароль.</p>
        </div>
        <div>
            <span>2</span>
            <strong>Завантажте файли</strong>
            <p>Додавайте один або кілька файлів і одразу обирайте папку.</p>
        </div>
        <div>
            <span>3</span>
            <strong>Керуйте щодня</strong>
            <p>Шукайте, переглядайте, завантажуйте або видаляйте непотрібне.</p>
        </div>
    </section>

    <section class="landing-final">
        <div>
            <span class="landing-label">Почніть зараз</span>
            <h2>Перші файли можна додати вже після реєстрації.</h2>
        </div>
        @auth
            <a class="button" href="{{ route('files.index') }}">Перейти до файлів</a>
        @else
            <a class="button" href="{{ route('register') }}">Почати безкоштовно</a>
        @endauth
    </section>
@endsection
