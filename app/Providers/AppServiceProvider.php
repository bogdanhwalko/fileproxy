<?php

namespace App\Providers;

use App\Database\SafeDatabaseMigrationRepository;
use App\Models\ManagedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('migration.repository', function ($repository, $app) {
            return new SafeDatabaseMigrationRepository(
                $app['db'],
                $app['config']['database.migrations']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Blade::directive('vasset', function ($expression) {
            return "<?php echo \\App\\Providers\\AppServiceProvider::versionedAsset({$expression}); ?>";
        });

        View::composer('components.app-topbar', function ($view) {
            $count = 0;

            try {
                $user = Auth::user();

                if ($user && Schema::hasColumn('managed_files', 'status')) {
                    $count = (int) ManagedFile::query()
                        ->where('user_id', $user->id)
                        ->where('status', ManagedFile::STATUS_FAILED)
                        ->count();
                }
            } catch (Throwable) {
                $count = 0;
            }

            $view->with('failedFilesCount', $count);
        });
    }

    public static function versionedAsset(string $path): string
    {
        $url = asset($path);
        $abs = public_path($path);

        if (is_file($abs)) {
            $sep = str_contains($url, '?') ? '&' : '?';
            $url .= $sep.'v='.filemtime($abs);
        }

        return $url;
    }
}
