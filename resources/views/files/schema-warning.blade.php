@extends('layouts.site')

@section('title', 'Потрібні міграції - FileProxy')

@section('content')
    <nav class="site-nav">
        <a class="brand" href="{{ route('home') }}">
            <div class="brand-mark">FP</div>
            <div>
                <strong>FileProxy</strong>
                <p>Підготовка бази даних</p>
            </div>
        </a>

        <div class="nav-actions">
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button class="button secondary" type="submit">Вийти</button>
            </form>
        </div>
    </nav>

    <section class="panel auth-panel">
        <h1>База даних не готова</h1>
        <p>Користувача створено, але файловий кабінет не може відкритися, бо на сервері не виконані всі міграції.</p>

        <div class="errors">
            <strong>Що потрібно виправити:</strong>
            <ul>
                @foreach ($problems as $problem)
                    <li>{{ $problem }}</li>
                @endforeach
            </ul>
        </div>

        <p>На cPanel у папці проєкту виконайте:</p>

        <code class="command-pill">php artisan migrate --force</code>
        <code class="command-pill">php artisan optimize:clear</code>
    </section>
@endsection
