<?php

namespace App\Providers;

use App\Database\SafeDatabaseMigrationRepository;
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
    }
}
