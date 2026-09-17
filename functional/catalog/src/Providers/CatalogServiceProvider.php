<?php

namespace Functional\Catalog\Providers;

use Functional\Catalog\Rest\Controls\ApplicationControl;
use Functional\Catalog\Rest\Controls\ApplicationRoleControl;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class CatalogServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }

        (new Access)->addControls([new ApplicationControl, new ApplicationRoleControl]);

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
