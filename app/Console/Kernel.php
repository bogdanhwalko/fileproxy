<?php

namespace App\Console;

use App\Services\ManagedFileStorageService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function (): void {
            $this->cleanupTempDirectory(storage_path('app/file-archives'), 3600);
            $this->cleanupTempDirectory(storage_path('app/share-zips'), 3600);
            $this->cleanupTempDirectory(storage_path('app/telegram-temp'), 3600);
            $this->cleanupTempDirectory(storage_path('app/uploads-pending'), 86400);
            $this->cleanupTempDirectory(storage_path('app/protected-chunks-tmp'), 3600);
            $this->cleanupTempDirectory(storage_path('app/upload-sessions'), 3 * 3600);
        })->name('fileproxy:cleanup-temp')->hourly()->withoutOverlapping();

        // Decrypted protected-file preview cache holds plaintext on disk —
        // swept far more often than the general hourly job above so a stale
        // copy never lingers for more than ~10 minutes past its TTL, while the
        // TTL itself stays long enough to avoid re-fetching/re-decrypting from
        // Telegram on every request while someone is actively viewing/seeking.
        $schedule->call(function (): void {
            $this->cleanupTempDirectory(
                storage_path('app/protected-preview-cache'),
                ManagedFileStorageService::PROTECTED_PREVIEW_CACHE_TTL
            );
        })->name('fileproxy:cleanup-protected-preview-cache')->everyTenMinutes()->withoutOverlapping();
    }

    private function cleanupTempDirectory(string $directory, int $olderThanSeconds): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $threshold = time() - $olderThanSeconds;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $entry) {
            if ($entry->isFile() && $entry->getMTime() < $threshold) {
                @unlink($entry->getPathname());
            }
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
