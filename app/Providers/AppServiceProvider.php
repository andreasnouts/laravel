<?php

namespace App\Providers;

use App\Contracts\IAuditLogger;
use App\Logging\LaravelLogger;
use App\Services\DatabaseAuditLogger;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\IBookingRepository::class,
            \App\Repositories\EloquentBookingRepository::class
        );

        // bind DatabaseLogger implementation to the IAuditLogger Interface,
        // use function() to return new DatabaseAuditLogger since we also need to pass the inner logger instance to
        // its constructor...
        $this->app->bind(IAuditLogger::class, function () {
            return new DatabaseAuditLogger(new LaravelLogger());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
