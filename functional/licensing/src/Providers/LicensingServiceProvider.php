<?php

namespace Functional\Licensing\Providers;

use Functional\Licensing\Database\Seeders\LicensingSeeder;
use Functional\Licensing\Guards\OrganizationHoldsAValidLicense;
use Functional\Licensing\Guards\UserHoldsAnAccess;
use Technical\Oidc\AuthorizationGuards;
use Xefi\LaravelOSDD\LayerServiceProvider;

class LicensingServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([LicensingSeeder::class], priority: 20);
        }

        $guards = $this->app->make(AuthorizationGuards::class);
        $guards->register(new OrganizationHoldsAValidLicense, priority: 200);
        $guards->register(new UserHoldsAnAccess, priority: 300);

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
