<?php

namespace Technical\Oidc\Providers;

use DateInterval;
use Laravel\Passport\Bridge;
use Nyholm\Psr7\Response;
use OpenIDConnect\Laravel\LaravelCurrentRequestService;
use OpenIDConnect\Laravel\PassportServiceProvider;
use Technical\Oidc\Grant\AuthCodeGrant;

class OidcPassportServiceProvider extends PassportServiceProvider
{
    protected function buildAuthCodeGrant(): AuthCodeGrant
    {
        return new AuthCodeGrant(
            $this->app->make(Bridge\AuthCodeRepository::class),
            $this->app->make(Bridge\RefreshTokenRepository::class),
            new DateInterval('PT10M'),
            new Response,
            $this->app->make(LaravelCurrentRequestService::class),
        );
    }
}
