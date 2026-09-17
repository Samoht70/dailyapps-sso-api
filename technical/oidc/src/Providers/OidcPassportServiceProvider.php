<?php

namespace Technical\Oidc\Providers;

use DateInterval;
use Illuminate\Encryption\Encrypter;
use Laravel\Passport\Bridge;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Nyholm\Psr7\Response;
use OpenIDConnect\Claims\ClaimSet;
use OpenIDConnect\IdTokenResponse;
use OpenIDConnect\Laravel\LaravelCurrentRequestService;
use OpenIDConnect\Laravel\PassportServiceProvider;
use Technical\Oidc\ClaimExtractor;
use Technical\Oidc\Grant\AuthCodeGrant;
use Technical\Oidc\Grant\RefreshTokenGrant;

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

    protected function makeRefreshTokenGrant(): RefreshTokenGrant
    {
        return tap(
            new RefreshTokenGrant($this->app->make(Bridge\RefreshTokenRepository::class)),
            fn (RefreshTokenGrant $grant) => $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn()),
        );
    }

    /**
     * Copied from the package to swap in the claim extractor that lets `profile`
     * carry `organization`, which the package seals shut.
     */
    public function makeAuthorizationServer(?ResponseTypeInterface $responseType = null): AuthorizationServer
    {
        $cryptKey = $this->makeCryptKey('private');
        $encryptionKey = $this->getEncryptionKey($this->app->make(Encrypter::class)->getKey());

        $responseType = new IdTokenResponse(
            $this->app->make(config('openid.repositories.identity')),
            $this->claimExtractor(),
            Configuration::forSymmetricSigner(
                $this->app->make(config('openid.signer')),
                InMemory::plainText($cryptKey->getKeyContents(), $cryptKey->getPassPhrase() ?? ''),
            ),
            config('openid.token_headers'),
            config('openid.use_microseconds'),
            $this->app->make(LaravelCurrentRequestService::class),
            $encryptionKey,
            config('openid.issuedBy', 'laravel'),
        );

        return new AuthorizationServer(
            $this->app->make(Bridge\ClientRepository::class),
            $this->app->make(Bridge\AccessTokenRepository::class),
            $this->app->make(Bridge\ScopeRepository::class),
            $cryptKey,
            $encryptionKey,
            $responseType,
        );
    }

    public function registerClaimExtractor(): void
    {
        $this->app->singleton(\OpenIDConnect\ClaimExtractor::class, fn (): ClaimExtractor => $this->claimExtractor());
    }

    private function claimExtractor(): ClaimExtractor
    {
        $sets = array_map(
            fn (array $claims, string $scope): ClaimSet => new ClaimSet($scope, $claims),
            $configured = config('openid.custom_claim_sets'),
            array_keys($configured),
        );

        return new ClaimExtractor(...$sets);
    }
}
