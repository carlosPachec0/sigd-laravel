<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(function () {
            $rule = Password::min(10)->mixedCase()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        RateLimiter::for('signup', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)
            ->by($request->ip().'|'.$request->input('email')));

        RateLimiter::for('forgot-password', fn (Request $request) => Limit::perMinute(3)
            ->by($request->ip().'|'.$request->input('email')));

        RateLimiter::for('resend-verification', fn (Request $request) => Limit::perMinute(3)
            ->by($request->user()?->id ?? $request->ip()));

        RateLimiter::for('verify-email', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
