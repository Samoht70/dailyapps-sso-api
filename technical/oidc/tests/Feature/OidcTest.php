<?php

namespace Technical\Oidc\Tests\Feature;

use DateInterval;
use Laravel\Passport\Bridge;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use OpenIDConnect\Laravel\LaravelCurrentRequestService;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Grant\AuthCodeGrant;
use Technical\Oidc\Models\Client;
use Technical\Oidc\Providers\OidcPassportServiceProvider;
use Tests\TestCase;

class OidcTest extends TestCase
{
    #[Test]
    public function it_hands_passport_the_layer_client_model(): void
    {
        $this->assertSame(Client::class, Passport::clientModel());
    }

    #[Test]
    public function it_expires_an_access_token_after_fifteen_minutes(): void
    {
        $lifetime = Passport::tokensExpireIn();

        $this->assertSame(15, $lifetime->i);
        $this->assertSame(0, $lifetime->h);
        $this->assertSame(0, $lifetime->d);
    }

    #[Test]
    public function it_expires_a_refresh_token_after_eight_hours(): void
    {
        $lifetime = Passport::refreshTokensExpireIn();

        $this->assertSame(8, $lifetime->h);
        $this->assertSame(0, $lifetime->d);
    }

    #[Test]
    public function it_leaves_the_password_implicit_and_device_grants_closed(): void
    {
        $this->assertFalse(Passport::$passwordGrantEnabled);
        $this->assertFalse(Passport::$implicitGrantEnabled);
        $this->assertFalse(Passport::$deviceCodeGrantEnabled);
    }

    #[Test]
    public function it_builds_the_authorization_server_from_the_layer_provider(): void
    {
        $this->assertArrayHasKey(OidcPassportServiceProvider::class, $this->app->getLoadedProviders());
        $this->assertInstanceOf(AuthorizationServer::class, $this->app->make(AuthorizationServer::class));
    }

    #[Test]
    public function it_turns_away_an_authorization_request_that_brings_no_pkce_challenge(): void
    {
        $this->expectException(OAuthServerException::class);

        $this->grant()->validateAuthorizationRequest(
            new ServerRequest('GET', '/oauth/authorize?client_id=any&response_type=code'),
        );
    }

    #[Test]
    public function it_turns_away_a_plain_code_challenge(): void
    {
        $this->expectException(OAuthServerException::class);

        $this->grant()->validateAuthorizationRequest(
            new ServerRequest(
                'GET',
                '/oauth/authorize?client_id=any&response_type=code&code_challenge=abc&code_challenge_method=plain',
            ),
        );
    }

    #[Test]
    public function it_only_accepts_the_s256_challenge_method(): void
    {
        $this->assertSame('S256', config('passport.code_challenge_method'));
    }

    private function grant(): AuthCodeGrant
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
