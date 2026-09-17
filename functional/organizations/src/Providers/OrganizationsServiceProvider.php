<?php

namespace Functional\Organizations\Providers;

use Functional\Organizations\Database\Seeders\OrganizationsSeeder;
use Functional\Organizations\Rest\Controls\OrganizationControl;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class OrganizationsServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([OrganizationsSeeder::class], priority: 10);
        }

        (new Access)->addControls([new OrganizationControl]);

        $this->withRouting(
            api: __DIR__.'/../../routes/api.php',
            apiPrefix: '',
        );
    }

    public function register(): void
    {
        //
    }
}
