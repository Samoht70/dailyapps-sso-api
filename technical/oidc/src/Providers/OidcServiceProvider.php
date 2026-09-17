<?php

namespace Technical\Oidc\Providers;

use DateInterval;
use Functional\Users\Events\PasswordChanged;
use Functional\Users\Listeners\CloseSessionsOnPasswordChange;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Http\Controllers\AuthorizationController as PassportAuthorizationController;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use Technical\Oidc\AuthorizationGuards;
use Technical\Oidc\ClaimsRegistry;
use Technical\Oidc\Exceptions\ConsentScreenNotAvailable;
use Technical\Oidc\Guards\AccountIsAvailable;
use Technical\Oidc\Http\Controllers\AuthorizationController;
use Technical\Oidc\Http\Middleware\EnforceSsoSessionLifetime;
use Technical\Oidc\Listeners\OpenSsoSession;
use Technical\Oidc\Listeners\RecordAuthenticationEvents;
use Technical\Oidc\Livewire\Account;
use Technical\Oidc\Livewire\ForgotPassword;
use Technical\Oidc\Livewire\Login;
use Technical\Oidc\Livewire\PingCounter;
use Technical\Oidc\Livewire\ResetPassword;
use Technical\Oidc\Models\Client;
use Xefi\LaravelOSDD\LayerServiceProvider;

class OidcServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        }

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'oidc');
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'oidc');

        $this->app->make(AuthorizationGuards::class)->register(new AccountIsAvailable, priority: 100);

        $this->registerScreens();
        $this->registerPassport();
        $this->registerListeners();

        $this->app->make(Kernel::class)->appendMiddlewareToGroup('web', EnforceSsoSessionLifetime::class);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
        );
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/passport.php', 'passport');
        $this->mergeConfigFrom(__DIR__.'/../../config/openid.php', 'openid');
        $this->mergeConfigFrom(__DIR__.'/../../config/oidc.php', 'oidc');

        $this->app->singleton(ClaimsRegistry::class);
        $this->app->singleton(AuthorizationGuards::class);

        $this->app->bind(PassportAuthorizationController::class, AuthorizationController::class);

        $this->app->when(AuthorizationController::class)
            ->needs(StatefulGuard::class)
            ->give(fn (): StatefulGuard => Auth::guard(config('passport.guard')));
    }

    private function registerScreens(): void
    {
        Livewire::component('oidc.ping-counter', PingCounter::class);
        Livewire::component('oidc.login', Login::class);
        Livewire::component('oidc.forgot-password', ForgotPassword::class);
        Livewire::component('oidc.reset-password', ResetPassword::class);
        Livewire::component('oidc.account', Account::class);
    }

    private function registerPassport(): void
    {
        Passport::useClientModel(Client::class);

        Passport::tokensExpireIn(new DateInterval('PT'.config('passport.access_token_minutes').'M'));
        Passport::refreshTokensExpireIn(new DateInterval('PT'.config('passport.refresh_token_hours').'H'));
        Passport::$deviceCodeGrantEnabled = false;

        Passport::authorizationView(
            fn (array $parameters) => throw ConsentScreenNotAvailable::forClient($parameters['client']->name),
        );

        Passport::tokensCan([
            'openid' => __('oidc::scopes.openid'),
            'profile' => __('oidc::scopes.profile'),
            'email' => __('oidc::scopes.email'),
            'applications' => __('oidc::scopes.applications'),
        ]);
    }

    private function registerListeners(): void
    {
        Event::listen(LoginEvent::class, OpenSsoSession::class);
        Event::listen(LoginEvent::class, [RecordAuthenticationEvents::class, 'handleLogin']);
        Event::listen(Failed::class, [RecordAuthenticationEvents::class, 'handleFailure']);
        Event::listen(PasswordResetLinkSent::class, [RecordAuthenticationEvents::class, 'handleResetLinkSent']);
        Event::listen(PasswordChanged::class, CloseSessionsOnPasswordChange::class);
        Event::listen(PasswordChanged::class, [RecordAuthenticationEvents::class, 'handlePasswordChanged']);
    }
}
