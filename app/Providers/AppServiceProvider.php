<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
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
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('cms-level-preview', function (Request $request) {
            $userKey = (string) optional($request->user())->id;
            $levelKey = (string) $request->route()->parameter('level');

            return Limit::perMinute(3)->by($userKey.'|'.$levelKey.'|'.$request->ip());
        });

        Vite::prefetch(concurrency: 3);
    }
}
