@extends('layouts.site')

@section('title', 'Зв`язок з розробником — FileProxy')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-app-topbar title="FileProxy" subtitle="Зв`язок з розробником" />

    <section class="feedback-hero">
        <div class="feedback-hero-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
            </svg>
        </div>
        <div>
            <h1>Зв`язок з розробником</h1>
            <p>Поділіться ідеєю, повідомте про баг або поставте питання. Читаємо все.</p>
        </div>
    </section>

    @if (session('status'))
        <div class="status feedback-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="errors">
            <strong>Перевірте дані форми.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel feedback-panel">
        <form action="{{ route('feedback.store') }}" method="post" class="feedback-form">
            @csrf

            <fieldset class="feedback-type-picker">
                <legend>Тип звернення</legend>
                <div class="feedback-type-grid">
                    @php
                        $typeIcons = [
                            'idea' => ['💡', 'Поділіться ідеєю, як покращити сервіс'],
                            'bug' => ['🐞', 'Помітили помилку чи дивну поведінку'],
                            'question' => ['❓', 'Поставте питання про роботу сайту'],
                            'other' => ['💬', 'Будь-яке інше повідомлення'],
                        ];
                    @endphp
                    @foreach ($types as $key => $label)
                        <label class="feedback-type-card">
                            <input type="radio" name="type" value="{{ $key }}" @checked(old('type', 'idea') === $key) required>
                            <span class="feedback-type-card-body">
                                <span class="feedback-type-emoji" aria-hidden="true">{{ $typeIcons[$key][0] ?? '✉' }}</span>
                                <strong>{{ $label }}</strong>
                                <small>{{ $typeIcons[$key][1] ?? '' }}</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="field-group">
                <label for="feedback-subject">
                    Заголовок
                    <span class="required-mark">*</span>
                </label>
                <input id="feedback-subject" class="field" type="text" name="subject" value="{{ old('subject') }}" maxlength="200" minlength="3" required placeholder="Коротко: про що повідомлення">
            </div>

            <div class="field-group">
                <label for="feedback-message">
                    Деталі
                    <span class="required-mark">*</span>
                </label>
                <textarea id="feedback-message" class="field feedback-textarea" name="message" rows="8" maxlength="5000" minlength="10" required placeholder="Опишіть якомога детальніше. Якщо це баг — крок за кроком, як його відтворити. Якщо ідея — навіщо вона потрібна.">{{ old('message') }}</textarea>
                <p class="field-hint">Максимум 5000 символів. Чим конкретніше — тим швидше зможемо допомогти.</p>
            </div>

            <div class="field-group">
                <label for="feedback-contact">
                    Альтернативний контакт <span class="field-optional">(опційно)</span>
                </label>
                <input id="feedback-contact" class="field" type="text" name="contact" value="{{ old('contact') }}" maxlength="200" placeholder="Telegram, email або інше — якщо хочете щоб ми відповіли інакше">
                <p class="field-hint">За замовчуванням ми бачимо ваш email з акаунту: <strong>{{ auth()->user()->email }}</strong></p>
            </div>

            <div class="feedback-form-actions">
                <a href="{{ route('files.index') }}" class="button secondary">Скасувати</a>
                <button type="submit" class="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 2 11 13"/>
                        <path d="M22 2 15 22l-4-9-9-4z"/>
                    </svg>
                    Надіслати
                </button>
            </div>
        </form>
    </section>

    @if ($recentCount > 0)
        <p class="feedback-history-hint">
            Ви вже надіслали {{ $recentCount }} {{ $recentCount === 1 ? 'повідомлення' : ($recentCount < 5 ? 'повідомлення' : 'повідомлень') }}. Дякуємо за внесок!
        </p>
    @endif
@endsection
