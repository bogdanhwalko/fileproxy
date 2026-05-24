<?php

namespace App\Jobs;

use App\Models\ManagedFile;
use App\Services\ProtectedFileService;
use App\Services\TelegramFileStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UploadManagedFileToTelegram implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 1800; // 30 min — protected files with many chunks can take a while

    public array $backoff = [10, 60, 300];

    public function __construct(public ManagedFile $file) {}

    public function handle(TelegramFileStorageService $telegram, ProtectedFileService $protected): void
    {
        $file = $this->file->fresh();

        if (! $file || $file->status !== ManagedFile::STATUS_PENDING) {
            return;
        }

        try {
            if ($file->is_protected) {
                $this->uploadProtected($file, $protected);
            } else {
                $this->upload($file, $telegram);
            }
        } catch (Throwable $exception) {
            $isFinalAttempt = $this->attempts() >= $this->tries;
            $isSyncQueue = (string) config('queue.default') === 'sync';

            if ($isFinalAttempt || $isSyncQueue) {
                $this->markFailed($file, $exception->getMessage());

                return;
            }

            throw $exception;
        }
    }

    private function uploadProtected(ManagedFile $file, ProtectedFileService $protected): void
    {
        // Scatter across every storage group the user has bots for. Falls back to
        // the per-file telegramStorageGroup if no extra groups exist.
        $user = $file->user()->first();

        if (! $user) {
            $this->markFailed($file, 'Користувача не знайдено.');
            return;
        }

        $groups = $user->telegramStorageGroups()->with('botToken')->get()
            ->filter(fn ($g) => $g->botToken !== null);

        if ($groups->isEmpty()) {
            // Fall back to the originally selected group only
            $primary = $file->telegramStorageGroup()->with('botToken')->first();
            if (! $primary || ! $primary->botToken) {
                $this->markFailed($file, 'Telegram-група або бот недоступні для захищеного файла.');
                return;
            }
            $groups = collect([$primary]);
        }

        $protected->uploadFromPendingPath($file, $groups);
    }

    private function upload(ManagedFile $file, TelegramFileStorageService $telegram): void
    {
        $group = $file->telegramStorageGroup()->with('botToken')->first();

        if (! $group || ! $group->botToken) {
            $this->markFailed($file, 'Telegram-група або бот недоступні.');

            return;
        }

        $absolutePath = Storage::disk('local')->path($file->path);

        if (! is_file($absolutePath)) {
            $this->markFailed($file, 'Тимчасовий файл недоступний для завантаження.');

            return;
        }

        $result = $telegram->sendDocumentFromPath($absolutePath, $file->original_name, $group);

        $file->forceFill([
            'status' => ManagedFile::STATUS_UPLOADED,
            'upload_failure_reason' => null,
            'telegram_bot_token_id' => $group->telegram_bot_token_id,
            'telegram_chat_id' => $result['chat_id'],
            'telegram_message_id' => $result['message_id'],
            'telegram_file_id' => $result['file_id'],
            'telegram_file_unique_id' => $result['file_unique_id'],
            'mime_type' => $result['mime_type'] ?: $file->mime_type,
            'size' => $result['file_size'] ?: $file->size,
            'path' => 'telegram/'.$file->user_id.'/'.$file->stored_name,
        ])->save();

        @unlink($absolutePath);
    }

    public function failed(Throwable $exception): void
    {
        $file = $this->file->fresh();

        if (! $file) {
            return;
        }

        $this->markFailed($file, $exception->getMessage());
    }

    private function markFailed(ManagedFile $file, string $reason): void
    {
        $this->cleanupPendingFile($file);

        $file->forceFill([
            'status' => ManagedFile::STATUS_FAILED,
            'upload_failure_reason' => mb_substr(trim($reason), 0, 1000),
        ])->save();
    }

    private function cleanupPendingFile(ManagedFile $file): void
    {
        $path = (string) $file->path;

        if ($path === '' || ! str_starts_with($path, 'uploads-pending/')) {
            return;
        }

        try {
            Storage::disk('local')->delete($path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
