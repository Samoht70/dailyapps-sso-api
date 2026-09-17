<?php

namespace Technical\Framework\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Xefi\LaravelOSDD\LayerServiceProvider;

class FrameworkServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }

        $this->registerRateLimiters();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/framework.php', 'framework');
    }

    /**
     * Counted per caller where one is identified, per origin otherwise — the
     * same two axes the sign-in screen locks on, so a client cannot dodge the
     * limit by dropping its token.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(
            (int) config('framework.rate_limits.api_per_minute'),
        )->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }
}
