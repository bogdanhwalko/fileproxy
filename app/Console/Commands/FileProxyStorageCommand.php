<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FileProxyStorageCommand extends Command
{
    protected $signature = 'fileproxy:storage
        {--fix : Create missing directories and try to make them writable}
        {--mode=0775 : Directory permissions used with --fix}';

    protected $description = 'Check FileProxy storage and cache directories.';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $mode = octdec((string) $this->option('mode'));
        $failed = false;

        $paths = [
            storage_path(),
            storage_path('app'),
            storage_path('app/public'),
            storage_path('app/share-zips'),
            storage_path('app/telegram-temp'),
            storage_path('app/uploads'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        $this->table(['Path', 'Exists', 'Writable'], collect($paths)->map(function (string $path) use ($fix, $mode, &$failed): array {
            if (! is_dir($path) && $fix) {
                @mkdir($path, $mode, true);
            }

            if (is_dir($path) && $fix) {
                @chmod($path, $mode);
            }

            $exists = is_dir($path);
            $writable = $exists && is_writable($path);

            if (! $exists || ! $writable) {
                $failed = true;
            }

            return [
                $path,
                $exists ? 'yes' : 'no',
                $writable ? 'yes' : 'no',
            ];
        })->all());

        if ($failed) {
            $this->error($fix
                ? 'Some directories are still not writable. Fix ownership or permissions from cPanel/File Manager.'
                : 'Some directories are missing or not writable. Run php artisan fileproxy:storage --fix or fix permissions manually.');

            return self::FAILURE;
        }

        $this->info('Storage and cache directories are ready.');

        return self::SUCCESS;
    }
}
