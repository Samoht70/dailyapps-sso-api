<?php

namespace Technical\Oidc\Providers;

use Livewire\Livewire;
use Technical\Oidc\Livewire\PingCounter;
use Xefi\LaravelOSDD\LayerServiceProvider;

class OidcServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'oidc');

        Livewire::component('oidc.ping-counter', PingCounter::class);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
        );
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/passport.php', 'passport');
        $this->mergeConfigFrom(__DIR__.'/../../config/openid.php', 'openid');
    }
}
