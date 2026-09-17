<?php

namespace Technical\Oidc\Bridge;

use Laravel\Passport\Bridge\AccessTokenRepository as PassportAccessTokenRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Technical\Oidc\CurrentSsoSession;

class AccessTokenRepository extends PassportAccessTokenRepository
{
    /**
     * Binding the token to its SSO session is what lets a logout push name the
     * tokens to drop, and what carries the `sid` claim across refreshes.
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        parent::persistNewAccessToken($accessTokenEntity);

        Passport::token()->newQuery()
            ->whereKey($accessTokenEntity->getIdentifier())
            ->update(['sso_session_id' => app(CurrentSsoSession::class)->id()]);
    }
}
