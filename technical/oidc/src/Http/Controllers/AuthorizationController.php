<?php

namespace Technical\Oidc\Http\Controllers;

use Laravel\Passport\Http\Controllers\AuthorizationController as PassportAuthorizationController;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Technical\Oidc\Actions\AuthorizeUser;
use Technical\Oidc\Events\ApplicationEnteredSession;
use Technical\Oidc\Models\Client;
use Technical\Oidc\Models\SsoSession;

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

        $this->recordParticipation($client);

        return parent::approveRequest($authRequest, $psrResponse);
    }

    private function recordParticipation(Client $client): void
    {
        $sessionId = session()->get(SsoSession::SESSION_KEY);
        $session = $sessionId === null ? null : SsoSession::query()->find($sessionId);

        if ($session !== null && $client->application !== null) {
            ApplicationEnteredSession::dispatch($session, $client->application);
        }
    }
}
