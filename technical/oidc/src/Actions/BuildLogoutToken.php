<?php

namespace Technical\Oidc\Actions;

use DateTimeImmutable;
use Functional\Catalog\Models\Application;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Technical\Oidc\Enums\LogoutReason;
use Technical\Oidc\Models\SsoSession;

class BuildLogoutToken
{
    /**
     * Signed with the same key as the id_token, so an application verifies it
     * through the JWKS it already fetches and needs no second secret.
     */
    public function __invoke(SsoSession $session, Application $application, LogoutReason $reason): string
    {
        $issuedAt = new DateTimeImmutable;

        return $this->configuration()
            ->builder()
            ->issuedBy(url('/'))
            ->permittedFor($application->oauth_client_id)
            ->issuedAt($issuedAt)
            ->expiresAt($issuedAt->modify('+2 minutes'))
            ->identifiedBy((string) Str::uuid())
            ->relatedTo($session->user_id)
            ->withClaim('sid', $session->getKey())
            ->withClaim('events', ['http://schemas.openid.net/event/backchannel-logout' => (object) []])
            ->withClaim('reason', $reason->value)
            ->getToken($this->configuration()->signer(), $this->configuration()->signingKey())
            ->toString();
    }

    private function configuration(): Configuration
    {
        return Configuration::forSymmetricSigner(
            app(config('openid.signer')),
            InMemory::plainText(file_get_contents(Passport::keyPath('oauth-private.key'))),
        );
    }
}
