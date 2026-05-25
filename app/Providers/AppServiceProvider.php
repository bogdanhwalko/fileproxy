<?php

namespace App\Providers;

use App\Database\SafeDatabaseMigrationRepository;
use Carbon\Carbon;
use Carbon\CarbonInterval;
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

        // Ukrainian locale for Carbon → diffForHumans() returns "3 хв тому", "вчора" etc.
        Carbon::setLocale('uk');
        CarbonInterval::setLocale('uk');

        Blade::directive('vasset', function ($expression) {
            return "<?php echo \\App\\Providers\\AppServiceProvider::versionedAsset({$expression}); ?>";
        });

        // <x-rel-time :date="$file->created_at" /> → renders relative time with full date in title
        Blade::directive('reltime', function ($expression) {
            return "<?php \$__rt = ({$expression}); echo \$__rt ? '<time datetime=\"'.\$__rt->toIso8601String().'\" title=\"'.\$__rt->format('d.m.Y H:i').'\">'.\$__rt->diffForHumans().'</time>' : ''; ?>";
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
