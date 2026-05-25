@extends('layouts.site')

@section('title', 'Статистика — FileProxy')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-app-topbar title="FileProxy" subtitle="Статистика вашого сховища" />

    <section class="stats-page-hero">
        <span class="section-kicker">Огляд</span>
        <h1>Статистика сховища</h1>
        <p>Скільки файлів, скільки місця і де вони лежать.</p>
    </section>

    <section class="stats stats-v2" aria-label="Статистика сховища">
        <div class="stat stat-primary">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['total'] }}</strong>
                <span>Усього файлів</span>
            </div>
        </div>
        <div class="stat stat-accent">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M3 5v6c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                    <path d="M3 11v6c0 1.66 4 3 9 3s9-1.34 9-3v-6"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['storage'] }}</strong>
                <span>Зайнято місця</span>
            </div>
        </div>
        <div class="stat stat-violet">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['folders'] }}</strong>
                <span>Папки</span>
            </div>
        </div>
        <div class="stat stat-telegram">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 2 11 13"/>
                    <path d="M22 2 15 22l-4-9-9-4z"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['telegram'] }}</strong>
                <span>У Telegram</span>
            </div>
        </div>
        <div class="stat stat-ink">
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="18" cy="5" r="3"/>
                    <circle cx="6" cy="12" r="3"/>
                    <circle cx="18" cy="19" r="3"/>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                </svg>
            </span>
            <div class="stat-body">
                <strong>{{ $stats['shared_files'] }}</strong>
                <span>З публічним лінком</span>
            </div>
        </div>
    </section>

    @if (! empty($storageByCategory))
        @php
            $totalBytes = array_sum(array_column($storageByCategory, 'bytes'));
            $stops = [];
            $cumulative = 0;
            foreach ($storageByCategory as $row) {
                $start = $cumulative / max(1, $totalBytes) * 100;
                $cumulative += $row['bytes'];
                $end = $cumulative / max(1, $totalBytes) * 100;
                $stops[] = $row['color'].' '.number_format($start, 4, '.', '').'% '.number_format($end, 4, '.', '').'%';
            }
            $conicGradient = 'conic-gradient(' . implode(', ', $stops) . ')';
        @endphp
        <section class="panel storage-breakdown" aria-label="Розподіл місця за типами файлів">
            <div class="storage-breakdown-head">
                <h2>Розподіл місця</h2>
                <span class="storage-breakdown-total">{{ \App\Models\ManagedFile::formatBytes($totalBytes) }}</span>
            </div>

            <div class="storage-breakdown-body">
                <div class="storage-donut" aria-hidden="true" style="--donut:{{ $conicGradient }}">
                    <div class="storage-donut-hole">
                        <strong>{{ count($storageByCategory) }}</strong>
                        <span>{{ count($storageByCategory) === 1 ? 'тип' : (count($storageByCategory) < 5 ? 'типи' : 'типів') }}</span>
                    </div>
                </div>

                <ul class="storage-breakdown-legend">
                    @foreach ($storageByCategory as $row)
                        @php $pct = $totalBytes > 0 ? round($row['bytes'] / $totalBytes * 100, 1) : 0; @endphp
                        <li>
                            <span class="legend-swatch" style="background:{{ $row['color'] }}"></span>
                            <span class="legend-label">{{ $row['label'] }}</span>
                            <span class="legend-bytes">{{ \App\Models\ManagedFile::formatBytes($row['bytes']) }}</span>
                            <span class="legend-pct">{{ $pct }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section class="stats-grid">
        <article class="panel stats-panel">
            <div class="stats-panel-header">
                <h2>Найбільші файли</h2>
                <span class="stats-panel-meta">топ {{ $largestFiles->count() }}</span>
            </div>
            @forelse ($largestFiles as $file)
                <a class="stats-row" href="{{ route('files.preview', $file) }}">
                    <span class="stats-row-icon">{{ $file->type_label }}</span>
                    <span class="stats-row-name">
                        <strong>{{ $file->original_name }}</strong>
                        <span>{{ $file->folder?->name ?? 'Без папки' }}</span>
                    </span>
                    <span class="stats-row-value">{{ $file->human_size }}</span>
                </a>
            @empty
                <div class="stats-empty">Файлів ще немає.</div>
            @endforelse
        </article>

        <article class="panel stats-panel">
            <div class="stats-panel-header">
                <h2>Останні завантажені</h2>
                <span class="stats-panel-meta">останні {{ $recentFiles->count() }}</span>
            </div>
            @forelse ($recentFiles as $file)
                <a class="stats-row" href="{{ route('files.preview', $file) }}">
                    <span class="stats-row-icon">{{ $file->type_label }}</span>
                    <span class="stats-row-name">
                        <strong>{{ $file->original_name }}</strong>
                        <span>{{ $file->created_at->diffForHumans() }}</span>
                    </span>
                    <span class="stats-row-value">{{ $file->human_size }}</span>
                </a>
            @empty
                <div class="stats-empty">Файлів ще немає.</div>
            @endforelse
        </article>

        <article class="panel stats-panel stats-panel-wide">
            <div class="stats-panel-header">
                <h2>Папки за кількістю файлів</h2>
                <span class="stats-panel-meta">топ {{ $foldersByFiles->count() }}</span>
            </div>
            @forelse ($foldersByFiles as $folder)
                @php
                    $maxCount = $foldersByFiles->max('files_count') ?: 1;
                    $percent = $folder->files_count > 0 ? max(4, round(($folder->files_count / $maxCount) * 100)) : 0;
                @endphp
                <a class="stats-folder-row" href="{{ route('files.index', ['folder' => $folder->id]) }}">
                    <span class="stats-folder-name">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                        {{ $folder->name }}
                    </span>
                    <span class="stats-folder-bar">
                        <span class="stats-folder-bar-fill" style="width: {{ $percent }}%"></span>
                    </span>
                    <span class="stats-folder-count">{{ $folder->files_count }}</span>
                </a>
            @empty
                <div class="stats-empty">Жодної папки ще не створено.</div>
            @endforelse
        </article>
    </section>
@endsection
