<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemLimitsCommand extends Command
{
    protected $signature = 'system:limits';

    protected $description = 'Print effective PHP/server limits and diagnostic info for upload debugging';

    public function handle(): int
    {
        $this->info('=== FileProxy diagnostic ===');
        $this->line('PHP version: '.PHP_VERSION);
        $this->line('SAPI:        '.php_sapi_name());
        $this->line('Server:      '.($_SERVER['SERVER_SOFTWARE'] ?? '(cli — server unknown)'));
        $this->line('OS:          '.PHP_OS.' / '.php_uname('r'));
        $this->newLine();

        $this->info('--- PHP limits (ini_get) ---');
        $limits = [
            'post_max_size',
            'upload_max_filesize',
            'memory_limit',
            'max_execution_time',
            'max_input_time',
            'max_input_vars',
            'max_file_uploads',
            'default_socket_timeout',
            'upload_tmp_dir',
            'session.save_path',
        ];

        foreach ($limits as $key) {
            $val = ini_get($key);
            $bytes = $this->parsesBytes($key) ? $this->humanBytes($this->toBytes((string) $val)) : '';
            $this->line(sprintf('  %-26s %s%s', $key, $val ?: '(empty)', $bytes ? "   ({$bytes})" : ''));
        }
        $this->newLine();

        $this->info('--- Effective protected-upload check ---');
        $postMax = $this->toBytes((string) ini_get('post_max_size'));
        $uploadMax = $this->toBytes((string) ini_get('upload_max_filesize'));
        $memory = $this->toBytes((string) ini_get('memory_limit'));
        $hardLimit = min($postMax ?: PHP_INT_MAX, $uploadMax ?: PHP_INT_MAX);
        $this->line('  Real upload cap:        '.$this->humanBytes($hardLimit).'  (min of post_max_size, upload_max_filesize)');
        $this->line('  Memory limit:           '.($memory > 0 ? $this->humanBytes($memory) : 'unlimited'));
        $this->line('  App "protected" wants:  500 MB');
        if ($hardLimit < 500 * 1024 * 1024) {
            $this->warn('  ⚠️  Real upload cap is BELOW 500 MB — raise post_max_size and upload_max_filesize');
        } else {
            $this->info('  ✓ PHP-level limits are sufficient for 500 MB protected uploads');
        }
        $this->newLine();

        $this->info('--- Disk space ---');
        $paths = [
            'storage/app'           => storage_path('app'),
            'storage/app/uploads-pending' => storage_path('app/uploads-pending'),
            'storage/app/protected-chunks-tmp' => storage_path('app/protected-chunks-tmp'),
            'PHP upload_tmp_dir'    => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
            'sys_get_temp_dir()'    => sys_get_temp_dir(),
        ];

        foreach ($paths as $label => $path) {
            if (! is_dir($path)) {
                $this->line(sprintf('  %-38s (no dir)  %s', $label, $path));
                continue;
            }
            $free = @disk_free_space($path);
            $writable = is_writable($path) ? 'rw' : 'ro';
            $this->line(sprintf(
                '  %-38s %s   free %s   (%s)',
                $label,
                $path,
                $free !== false ? $this->humanBytes((int) $free) : '?',
                $writable
            ));
        }
        $this->newLine();

        $this->info('--- Extensions critical for FileProxy ---');
        foreach (['openssl', 'pdo_mysql', 'curl', 'fileinfo', 'mbstring', 'zip', 'gd', 'json'] as $ext) {
            $loaded = extension_loaded($ext);
            $this->line(sprintf('  %-12s %s', $ext, $loaded ? '✓' : '✗ MISSING'));
        }
        $this->newLine();

        $this->info('--- Database schema check ---');
        try {
            DB::connection()->getPdo();
            $this->line('  ✓ DB connection OK');
            $protectedCols = Schema::hasColumns('managed_files', ['is_protected', 'encryption_key', 'chunk_count', 'original_size']);
            $chunksTable = Schema::hasTable('managed_file_chunks');
            $this->line('  '.($protectedCols ? '✓' : '✗').' managed_files has protected columns');
            $this->line('  '.($chunksTable ? '✓' : '✗').' managed_file_chunks table exists');
        } catch (Throwable $e) {
            $this->error('  ✗ DB error: '.$e->getMessage());
        }
        $this->newLine();

        $this->info('--- App config ---');
        $this->line('  APP_ENV:    '.config('app.env'));
        $this->line('  APP_DEBUG:  '.(config('app.debug') ? 'true' : 'false'));
        $this->line('  APP_URL:    '.config('app.url'));
        $this->line('  QUEUE:      '.config('queue.default'));
        $this->line('  CACHE:      '.config('cache.default'));
        $this->line('  Timezone:   '.config('app.timezone'));
        $this->newLine();

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function parsesBytes(string $key): bool
    {
        return in_array($key, ['post_max_size', 'upload_max_filesize', 'memory_limit'], true);
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1' || $value === '0') {
            return $value === '-1' ? 0 : 0;
        }
        $last = strtolower($value[strlen($value) - 1]);
        $num = (int) $value;
        return match ($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1).' '.$units[$power];
    }
}
