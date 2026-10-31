<?php

namespace App\Providers;

use Carbon\Carbon;
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
        $this->app->singleton(\App\Services\Cart\CartService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('pt_BR');

        RateLimiter::for('chatbot', function (Request $request) {
            $key = $request->session()->getId() ?: $request->ip();

            return Limit::perMinute((int) config('services.chatbot.rate_limit_per_minute', 30))->by($key);
        });
    }
}
