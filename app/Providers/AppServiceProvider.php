<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
     * Mendaftarkan rate limiter bernama (PRD Bab 5.1).
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(
                config('sitamu.login.rate_limit_window_minutes'),
                config('sitamu.login.rate_limit_max'),
            )->by($request->ip());
        });
    }
}
