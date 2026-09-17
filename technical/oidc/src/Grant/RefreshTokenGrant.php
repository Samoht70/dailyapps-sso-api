<?php

namespace Technical\Oidc\Grant;

use DateInterval;
use Functional\Users\Models\User;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\RefreshTokenGrant as LeagueRefreshTokenGrant;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Technical\Oidc\Actions\AuthorizeUser;
use Technical\Oidc\Concerns\CarriesTheSsoSession;
use Technical\Oidc\Models\Client;

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
        $this->revalidateEntitlement($request);

        return parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);
    }

    /**
     * Every refresh re-asks the same question the authorization did — account,
     * organization and licence. One of the three has fallen and the application
     * is sent back to /oauth/authorize rather than handed a fresh token.
     *
     * @throws OAuthServerException `invalid_grant`, carrying the reason as hint
     */
    private function revalidateEntitlement(ServerRequestInterface $request): void
    {
        $refreshToken = $request->getParsedBody()['refresh_token'] ?? null;

        if ($refreshToken === null) {
            return;
        }

        $payload = json_decode($this->decrypt($refreshToken), true, 512, JSON_THROW_ON_ERROR);

        $account = User::query()->find($payload['user_id'] ?? null);
        $client = Client::query()->find($payload['client_id'] ?? null);

        if ($account === null || $client === null) {
            throw OAuthServerException::invalidGrant();
        }

        try {
            app(AuthorizeUser::class)($account, $client);
        } catch (OAuthServerException $refusal) {
            throw OAuthServerException::invalidGrant($refusal->getHint() ?? '');
        }
    }
}
