<?php

namespace Technical\Oidc\Http\Controllers;

use Laravel\Passport\Http\Controllers\AuthorizationController as PassportAuthorizationController;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Technical\Oidc\Actions\AuthorizeUser;
use Technical\Oidc\Models\Client;

class AuthorizationController extends PassportAuthorizationController
{
    /**
     * Entitlement is settled here rather than in a middleware, because this is
     * the first point where the redirect URI has been validated — refusing any
     * earlier would mean redirecting to an address nobody declared.
     */
    protected function approveRequest(AuthorizationRequestInterface $authRequest, ResponseInterface $psrResponse): Response
    {
        $client = Client::query()->findOrFail($authRequest->getClient()->getIdentifier());

        try {
            app(AuthorizeUser::class)(
                $this->guard->user(),
                $client,
                $authRequest->getRedirectUri(),
            );
        } catch (OAuthServerException $refusal) {
            return $this->convertResponse($refusal->generateHttpResponse($psrResponse));
        }

        return parent::approveRequest($authRequest, $psrResponse);
    }
}
