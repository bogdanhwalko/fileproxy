<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/files';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-uploads', function (Request $request) {
            return Limit::perMinute(30)->by('api-uploads:'.($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('auth-phone', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone')) ?: 'unknown';

            return [
                Limit::perMinute(5)->by('auth-phone-ip:'.$request->ip()),
                Limit::perMinute(5)->by('auth-phone:'.$phone),
            ];
        });

        RateLimiter::for('uploads', function (Request $request) {
            $perMinute = (int) env('UPLOADS_PER_MINUTE', 120);

            return Limit::perMinute($perMinute)->by('uploads:'.($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('share-download', function (Request $request) {
            return Limit::perMinute(20)->by('share-download:'.$request->ip());
        });

        // For dashboard inline/preview/download endpoints. With lazy-loading thumbnails
        // a single page may legitimately fetch ~10-30 images quickly; cap at a rate that
        // protects Telegram bots from abuse but doesn't break normal browsing.
        RateLimiter::for('file-stream', function (Request $request) {
            return Limit::perMinute(240)->by('file-stream:'.($request->user()?->id ?: $request->ip()));
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
