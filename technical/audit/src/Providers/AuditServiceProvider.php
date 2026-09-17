<?php

namespace Technical\Audit\Providers;

use Lomkit\Access\Access;
use Technical\Audit\Rest\Controls\SecurityEventControl;
use Xefi\LaravelOSDD\LayerServiceProvider;

class AuditServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }

        (new Access)->addControls([new SecurityEventControl]);

        $this->withRouting(
            api: __DIR__.'/../../routes/api.php',
            commands: __DIR__.'/../../routes/console.php',
            apiPrefix: '',
        );
    }

    public function register(): void
    {
        //
    }
}
