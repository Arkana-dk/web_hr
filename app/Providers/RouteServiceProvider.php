<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Path setelah login berhasil.
     */
    public const HOME = '/home';

    /**
     * Define bindings, pattern filters, dll.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // Semua route API
            Route::middleware('api')
                 ->prefix('api')
                 ->group(base_path('routes/api.php'));

            // Semua route Web
            Route::middleware('web')
                 ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Konfigurasi rate limiter.
     */
    protected function configureRateLimiting(): void
    {
        // RateLimiter::for('api', function (Request $request) {
        //     // Maks 60 request per menit per IP
        //     return Limit::perMinute(60)->by($request->ip());
        // });
    }
}
