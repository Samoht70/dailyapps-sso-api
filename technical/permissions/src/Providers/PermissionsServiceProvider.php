<?php

namespace Technical\Permissions\Providers;

use Xefi\LaravelOSDD\LayerServiceProvider;

class PermissionsServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }
    }

    public function register(): void {}
}
