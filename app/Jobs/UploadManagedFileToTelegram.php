<?php

namespace App\Jobs;

use App\Models\ManagedFile;
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

    public int $timeout = 300;

    public array $backoff = [10, 60, 300];

    public function __construct(public ManagedFile $file) {}

    public function handle(TelegramFileStorageService $telegram): void
    {
        $file = $this->file->fresh();

        if (! $file || $file->status !== ManagedFile::STATUS_PENDING) {
            return;
        }

        try {
            $this->upload($file, $telegram);
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
