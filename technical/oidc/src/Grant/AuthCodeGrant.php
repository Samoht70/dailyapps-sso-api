<?php

namespace Technical\Oidc\Grant;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use OpenIDConnect\Grant\AuthCodeGrant as OpenIdConnectAuthCodeGrant;
use Psr\Http\Message\ServerRequestInterface;

class AuthCodeGrant extends OpenIdConnectAuthCodeGrant
{
    /**
     * The OAuth2 server requires PKCE of public clients only; every client of
     * this ecosystem must bring one, confidential ones included.
     *
     * @throws OAuthServerException when the request carries no S256 code challenge
     */
    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequestInterface
    {
        $parameters = $request->getQueryParams();

        if (empty($parameters['code_challenge'])) {
            throw OAuthServerException::invalidRequest(
                'code_challenge',
                'Every client must authorize with PKCE.',
            );
        }

        if (($parameters['code_challenge_method'] ?? 'plain') !== config('passport.code_challenge_method')) {
            throw OAuthServerException::invalidRequest(
                'code_challenge_method',
                'Only the S256 code challenge method is accepted.',
            );
        }

        return parent::validateAuthorizationRequest($request);
    }
}
