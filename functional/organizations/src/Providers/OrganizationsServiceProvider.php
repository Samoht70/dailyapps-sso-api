<?php

namespace Functional\Organizations\Providers;

use Functional\Organizations\Database\Seeders\OrganizationsSeeder;
use Xefi\LaravelOSDD\LayerServiceProvider;

class OrganizationsServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([OrganizationsSeeder::class], priority: 10);
        }

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            api: __DIR__.'/../../routes/api.php',
            commands: __DIR__.'/../../routes/console.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }

    public function register(): void
    {
        //
    }
}
