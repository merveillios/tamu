<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Mengatur paginasi Bootstrap dan rate limiter bernama (PRD Bab 5.1).
     */
    public function boot(): void
    {
        // Tampilan paginasi mengikuti Bootstrap bawaan Volt.
        Paginator::useBootstrapFive();

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(
                config('sitamu.login.rate_limit_window_minutes'),
                config('sitamu.login.rate_limit_max'),
            )->by($request->ip());
        });
    }
}
