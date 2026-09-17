<?php

namespace Technical\Oidc\Grant;

use DateInterval;
use League\OAuth2\Server\Grant\RefreshTokenGrant as LeagueRefreshTokenGrant;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Technical\Oidc\Concerns\CarriesTheSsoSession;

class RefreshTokenGrant extends LeagueRefreshTokenGrant
{
    use CarriesTheSsoSession;

    /**
     * A refreshed token stays attached to the session that opened it, so the
     * `sid` claim survives a refresh and a logout push still reaches it.
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        $this->rememberSsoSessionFromRefreshToken($request);

        return parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);
    }
}
