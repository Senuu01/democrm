<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Completely disable database if DB_CONNECTION is null
        if (config('database.default') === 'null') {
            $this->app->bind('db', function () {
                throw new \Exception('Database is disabled for this application');
            });
            
            $this->app->bind(DatabaseManager::class, function () {
                throw new \Exception('Database is disabled for this application');
            });
        }
    }

    public function boot(): void
    {
        //
    }
}