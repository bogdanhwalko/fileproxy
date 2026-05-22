<?php

namespace App\Providers;

use App\Database\SafeDatabaseMigrationRepository;
use App\Models\ManagedFile;
use Illuminate\Support\Facades\Auth;
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
}
