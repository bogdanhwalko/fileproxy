<?php

namespace App\Providers;

use App\Database\SafeDatabaseMigrationRepository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
