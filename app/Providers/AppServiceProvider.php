<?php

namespace App\Providers;

use App\Contracts\IAuditLogger;
use App\Contracts\IBookingRepository;
use App\Contracts\IGuideRepository;
use App\Logging\LaravelLogger;
use App\Models\Guide;
use App\Repositories\EloquentBookingRepository;
use App\Repositories\EloquentGuideRepository;
use App\Services\DatabaseAuditLogger;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IBookingRepository::class, EloquentBookingRepository::class);
        $this->app->bind(IGuideRepository::class, EloquentGuideRepository::class);

        // bind DatabaseLogger implementation to the IAuditLogger Interface,
        // use function() to return new DatabaseAuditLogger since we also need to pass the inner logger instance to
        // its constructor...
        $this->app->bind(IAuditLogger::class, function () {
            return new DatabaseAuditLogger(new LaravelLogger());
        });


        Route::bind('guide', function ($value, $route) {
            /** @var IGuideRepository $guideRepo */
            $guideRepo = app(IGuideRepository::class);
            // Check if this is the admin route — bypass active-only scope
            if ($route->getName() === 'admin.guides.bookings.destroy')
            {
                return $guideRepo->getByHashIdWithoutGlobalScopes($value) ?? abort(404);
//                return Guide::withoutGlobalScopes()->findOrFail($value);
            }

//            return Guide::findOrFail($value); // normal scope applies
            return $guideRepo->getByHashId($value) ?? abort(404); // normal scope applies
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
