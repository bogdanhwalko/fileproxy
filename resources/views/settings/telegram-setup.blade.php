@extends('layouts.site')

@section('title', 'Прив’язка Telegram — FileProxy')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-app-topbar title="Прив’язка Telegram" subtitle="Підготовка групи для завантаження файлів" />

    <section class="settings-grid">
        <section class="panel">
            <div class="panel-header">
                <h2>Кроки підключення</h2>
                <p>Для завантаження файлів звичайний користувач має підключити Telegram-групу.</p>
            </div>

            <div class="settings-list">
                <article class="settings-item">
                    <div>
                        <strong>1. Створіть або виберіть бота</strong>
                        <span>У BotFather створіть бота, скопіюйте token і додайте його в FileProxy.</span>
                    </div>
                </article>
                <article class="settings-item">
                    <div>
                        <strong>2. Додайте бота в групу</strong>
                        <span>Бот має бути учасником групи, у яку FileProxy надсилатиме файли.</span>
                    </div>
                </article>
                <article class="settings-item">
                    <div>
                        <strong>3. Дізнайтесь Chat ID</strong>
                        <span>Надішліть повідомлення в групу і перегляньте update бота або використайте службового бота для визначення chat_id.</span>
                    </div>
                </article>
                <article class="settings-item">
                    <div>
                        <strong>4. Додайте групу в FileProxy</strong>
                        <span>Після додавання групи вона з’явиться у формі завантаження файлів.</span>
                    </div>
                </article>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Поточний стан</h2>
                <p>Перевірте, чи вже є бот і група для Telegram-сховища.</p>
            </div>

            <div class="settings-list">
                <article class="settings-item">
                    <div>
                        <strong>Боти</strong>
                        <span>{{ $botTokens->count() ? 'Додано: '.$botTokens->count() : 'Ще не додано' }}</span>
                    </div>
                </article>
                <article class="settings-item">
                    <div>
                        <strong>Групи</strong>
                        <span>{{ $storageGroups->count() ? 'Додано: '.$storageGroups->count() : 'Ще не додано' }}</span>
                    </div>
                </article>
                <a class="button" href="{{ route('telegram-settings.index') }}">Налаштувати Telegram-сховище</a>
            </div>
        </section>
    </section>
@endsection
