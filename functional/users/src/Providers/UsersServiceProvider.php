<?php

namespace Functional\Users\Providers;

use Functional\Users\Models\User;
use Functional\Users\Rest\Controls\UserControl;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class UsersServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }

        (new Access)->addControls([new UserControl]);

        $this->withRouting(
            api: __DIR__.'/../../routes/api.php',
            apiPrefix: '',
        );
    }

    public function register(): void
    {
        config(['auth.providers.users.model' => User::class]);
    }
}
