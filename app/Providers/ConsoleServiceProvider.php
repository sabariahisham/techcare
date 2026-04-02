<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\SyncUsersFromSpp;
use App\Console\Commands\SyncRefsData;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register specific commands manually to avoid auto-discovery conflicts
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncUsersFromSpp::class,
                SyncRefsData::class,
            ]);
        }
    }
}
