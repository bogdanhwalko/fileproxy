@extends('layouts.site')

@section('title', 'FileProxy — безкоштовне сховище файлів з Telegram-бекендом')
@section('description', 'Зберігайте, знаходьте і діліться файлами без хаосу. Особистий файловий кабінет з папками, швидким пошуком, переглядом у браузері та контрольованими публічними лінками. Безкоштовно і без обмежень.')
@section('og_title', 'FileProxy — безкоштовний файловий кабінет')

@push('head')
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'#org',
                    'name' => 'FileProxy',
                    'url' => url('/'),
                    'logo' => asset('favicon2.ico'),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'name' => 'FileProxy',
                    'url' => url('/'),
                    'inLanguage' => 'uk-UA',
                    'publisher' => ['@id' => url('/').'#org'],
                ],
                [
                    '@type' => 'WebApplication',
                    'name' => 'FileProxy',
                    'url' => url('/'),
                    'description' => 'Безкоштовне сховище і файловий кабінет з папками, пошуком, переглядом у браузері та контрольованими публічними посиланнями.',
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'browserRequirements' => 'Requires JavaScript and a modern browser',
                    'inLanguage' => 'uk-UA',
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '0',
                        'priceCurrency' => 'UAH',
                    ],
                    'featureList' => [
                        'Безкоштовне завантаження файлів',
                        'Папки і швидкий пошук',
                        'Перегляд у браузері без скачування',
                        'Контрольовані публічні посилання',
                        'Telegram-сховище',
                        'Приватність і власний контроль',
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Скільки коштує FileProxy?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'FileProxy безкоштовний. Ви платите 0 ₴ за використання сервісу.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Який максимальний розмір файлу?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'До 50 MB на файл — таке обмеження накладає Telegram Bot API. Кількість файлів не обмежена.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Де зберігаються мої файли?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'У вашій власній Telegram-групі через бота, якого ви налаштовуєте. Тільки ви маєте доступ до файлів.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Як ділитися файлами?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Створіть публічне посилання з обмеженням за кількістю переглядів або датою дії. Доступ можна закрити одним кліком.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

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
                <a class="button secondary nav-button" href="{{ route('files.index') }}">Кабінет</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="button secondary nav-button" type="submit">Вийти</button>
                </form>
            @else
                <a class="button secondary nav-button" href="{{ route('login') }}">Увійти</a>
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

    <section class="home-hero" aria-label="Заголовок FileProxy">
        <span class="home-hero-orb home-hero-orb-a" aria-hidden="true"></span>
        <span class="home-hero-orb home-hero-orb-b" aria-hidden="true"></span>

        <div class="home-hero-grid">
            <div class="home-hero-copy">
                <span class="home-hero-eyebrow">
                    <span class="home-hero-eyebrow-dot" aria-hidden="true"></span>
                    Безкоштовно · Без обмежень · Приватно
                </span>

                <h1 class="home-hero-title">
                    Зберігайте, знаходьте і діліться <span class="home-hero-accent">файлами</span> без хаосу в чатах
                </h1>

                <p class="home-hero-lead">
                    FileProxy — особистий файловий кабінет з папками, пошуком, переглядом у браузері
                    і контрольованими лінками. Підходить для документів, фото, архівів і робочих матеріалів.
                </p>

                <div class="home-hero-actions">
                    @auth
                        <a class="button button-large" href="{{ route('files.index') }}">
                            Відкрити кабінет
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="13 6 19 12 13 18"/>
                            </svg>
                        </a>
                        @if (auth()->user()->is_admin)
                            <a class="button secondary button-large" href="{{ route('admin.users.index') }}">Адмінка</a>
                        @endif
                    @else
                        <a class="button button-large" href="{{ route('register') }}">
                            Створити акаунт
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="13 6 19 12 13 18"/>
                            </svg>
                        </a>
                        <a class="button secondary button-large" href="{{ route('login') }}">У мене вже є акаунт</a>
                    @endauth
                </div>
            </div>

            <div class="home-hero-preview" aria-label="Приклад робочого простору">
                <div class="home-preview-window">
                    <div class="home-preview-bar">
                        <span class="home-preview-dot home-preview-dot-r"></span>
                        <span class="home-preview-dot home-preview-dot-y"></span>
                        <span class="home-preview-dot home-preview-dot-g"></span>
                        <span class="home-preview-url">fileproxy.online/files</span>
                    </div>

                    <div class="home-preview-body">
                        <aside class="home-preview-folders">
                            <span class="home-preview-folders-head">Папки</span>
                            <a class="home-preview-folder is-active">
                                <span>Усі файли</span><b>168</b>
                            </a>
                            <a class="home-preview-folder">
                                <span>Документи</span><b>42</b>
                            </a>
                            <a class="home-preview-folder">
                                <span>Фото</span><b>96</b>
                            </a>
                            <a class="home-preview-folder">
                                <span>Для клієнтів</span><b>12</b>
                            </a>
                        </aside>

                        <div class="home-preview-main">
                            <div class="home-preview-upload">
                                <span class="home-preview-upload-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 4v12"/>
                                        <polyline points="6 10 12 4 18 10"/>
                                        <line x1="4" y1="20" x2="20" y2="20"/>
                                    </svg>
                                </span>
                                <div>
                                    <strong>Перетягніть файли сюди</strong>
                                    <small>або оберіть з пристрою</small>
                                </div>
                            </div>

                            <ul class="home-preview-files">
                                <li>
                                    <span class="home-preview-file-badge home-preview-badge-pdf">PDF</span>
                                    <div>
                                        <strong>Договір.pdf</strong>
                                        <small>2.4 MB · лінк активний</small>
                                    </div>
                                    <span class="home-preview-status home-preview-status-ok">●</span>
                                </li>
                                <li>
                                    <span class="home-preview-file-badge home-preview-badge-img">IMG</span>
                                    <div>
                                        <strong>Фото товару.jpg</strong>
                                        <small>892 KB · перегляд</small>
                                    </div>
                                    <span class="home-preview-status home-preview-status-ok">●</span>
                                </li>
                                <li>
                                    <span class="home-preview-file-badge home-preview-badge-zip">ZIP</span>
                                    <div>
                                        <strong>Матеріали.zip</strong>
                                        <small>14 MB · 5 переглядів</small>
                                    </div>
                                    <span class="home-preview-status">●</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="home-preview-floater home-preview-floater-share" aria-hidden="true">
                    <span class="home-preview-floater-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                            <polyline points="16 6 12 2 8 6"/>
                            <line x1="12" y1="2" x2="12" y2="15"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Лінк створено</strong>
                        <small>Ліміт переглядів: 10</small>
                    </div>
                </div>

                <div class="home-preview-floater home-preview-floater-tg" aria-hidden="true">
                    <span class="home-preview-floater-icon home-preview-floater-icon-cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 2 11 13"/>
                            <path d="M22 2 15 22l-4-9-9-4z"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Telegram-сховище</strong>
                        <small>Файли в твоїх руках</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-features" aria-label="Можливості FileProxy">
        <header class="home-section-head">
            <span class="section-kicker">Що всередині</span>
            <h2>Усе, щоб тримати файли під контролем</h2>
        </header>

        <div class="home-feature-grid">
            <article class="home-feature">
                <span class="home-feature-icon home-feature-icon-indigo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v6m0 0L9 5m3 3 3-3"/>
                        <path d="M3 13a9 9 0 0 0 18 0"/>
                        <path d="M3 13v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"/>
                    </svg>
                </span>
                <strong>Безкоштовне завантаження</strong>
                <p>Починай без оплати. Підключи власне Telegram-сховище, коли файлів стане більше.</p>
            </article>

            <article class="home-feature">
                <span class="home-feature-icon home-feature-icon-cyan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    </svg>
                </span>
                <strong>Папки замість хаосу</strong>
                <p>Розкладай документи, фото та архіви по зрозумілих розділах і знаходь за секунди.</p>
            </article>

            <article class="home-feature">
                <span class="home-feature-icon home-feature-icon-pink">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <line x1="21" y1="21" x2="16.5" y2="16.5"/>
                    </svg>
                </span>
                <strong>Швидкий пошук</strong>
                <p>За назвою, MIME-типом або розширенням. 12 категорій типів файлів — від відео до шрифтів.</p>
            </article>

            <article class="home-feature">
                <span class="home-feature-icon home-feature-icon-teal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </span>
                <strong>Перегляд у браузері</strong>
                <p>Зображення й текстові файли відкриваються одразу — не треба нічого скачувати.</p>
            </article>

            <article class="home-feature">
                <span class="home-feature-icon home-feature-icon-amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18" cy="5" r="3"/>
                        <circle cx="6" cy="12" r="3"/>
                        <circle cx="18" cy="19" r="3"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                    </svg>
                </span>
                <strong>Контрольовані посилання</strong>
                <p>Лімітуй кількість переглядів і дату дії. Закрий доступ одним кліком.</p>
            </article>

            <article class="home-feature">
                <span class="home-feature-icon home-feature-icon-violet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5z"/>
                        <polyline points="9 12 11 14 15 10"/>
                    </svg>
                </span>
                <strong>Приватність</strong>
                <p>Файли в твоїй Telegram-групі. Ніхто чужий не побачить — і ти можеш забрати їх будь-коли.</p>
            </article>
        </div>
    </section>

    <section class="home-steps" aria-label="Як почати">
        <header class="home-section-head">
            <span class="section-kicker">Як це працює</span>
            <h2>Три кроки до порядку у файлах</h2>
        </header>

        <div class="home-steps-grid">
            <article class="home-step">
                <span class="home-step-num">1</span>
                <strong>Зареєструйся за номером</strong>
                <p>Натисни «Створити акаунт», введи телефон і підтверди код через Telegram-бота.</p>
            </article>
            <article class="home-step">
                <span class="home-step-num">2</span>
                <strong>Завантаж перші файли</strong>
                <p>Перетягни файли у dropzone або обери з пристрою. Без обмежень кількості.</p>
            </article>
            <article class="home-step">
                <span class="home-step-num">3</span>
                <strong>Ділись лінками</strong>
                <p>Створи публічне посилання з лімітом переглядів і відправ комусь — або тримай приватно.</p>
            </article>
        </div>
    </section>

    <section class="home-cta" aria-label="Заклик до дії">
        <span class="home-cta-orb home-cta-orb-a" aria-hidden="true"></span>
        <span class="home-cta-orb home-cta-orb-b" aria-hidden="true"></span>

        <div class="home-cta-text">
            <span class="home-cta-eyebrow">Почніть зараз</span>
            <h2>Створіть безкоштовний кабінет і наведіть порядок у файлах уже сьогодні</h2>
        </div>
        <div class="home-cta-actions">
            @auth
                <a class="button button-large" href="{{ route('files.index') }}">Перейти до файлів</a>
            @else
                <a class="button button-large" href="{{ route('register') }}">
                    Почати безкоштовно
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="13 6 19 12 13 18"/>
                    </svg>
                </a>
            @endauth
        </div>
    </section>
@endsection
