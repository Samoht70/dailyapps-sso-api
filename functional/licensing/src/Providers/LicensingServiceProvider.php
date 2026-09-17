<?php

namespace Functional\Licensing\Providers;

use Functional\Licensing\Database\Seeders\LicensingSeeder;
use Functional\Licensing\Guards\OrganizationHoldsAValidLicense;
use Functional\Licensing\Guards\UserHoldsAnAccess;
use Functional\Licensing\Oidc\LicensingClaimsProvider;
use Functional\Licensing\Rest\Controls\ApplicationAccessControl;
use Functional\Licensing\Rest\Controls\LicenseControl;
use Lomkit\Access\Access;
use Technical\Oidc\AuthorizationGuards;
use Technical\Oidc\ClaimsRegistry;
use Xefi\LaravelOSDD\LayerServiceProvider;

class LicensingServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([LicensingSeeder::class], priority: 20);
        }

        $this->app->make(ClaimsRegistry::class)->register(new LicensingClaimsProvider);

        $guards = $this->app->make(AuthorizationGuards::class);
        $guards->register(new OrganizationHoldsAValidLicense, priority: 200);
        $guards->register(new UserHoldsAnAccess, priority: 300);

        (new Access)->addControls([new LicenseControl, new ApplicationAccessControl]);

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
