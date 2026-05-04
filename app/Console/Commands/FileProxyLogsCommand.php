<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FileProxyLogsCommand extends Command
{
    protected $signature = 'fileproxy:logs {--lines=120 : Number of log lines to show}';

    protected $description = 'Show recent Laravel log lines.';

    public function handle(): int
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            $this->warn("Log file does not exist: {$path}");

            return self::SUCCESS;
        }

        if (! is_readable($path)) {
            $this->error("Log file is not readable: {$path}");

            return self::FAILURE;
        }

        $lines = max(1, min(1000, (int) $this->option('lines')));
        $content = file($path, FILE_IGNORE_NEW_LINES);

        if ($content === false) {
            $this->error("Could not read log file: {$path}");

            return self::FAILURE;
        }

        foreach (array_slice($content, -$lines) as $line) {
            $this->line($line);
        }

        return self::SUCCESS;
    }
}
