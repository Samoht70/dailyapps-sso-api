<?php

namespace Technical\Oidc\Actions;

use Technical\Oidc\Models\Client;

class ResolveCallingClient
{
    /**
     * Two callers ask for claims: the token endpoint, which names its client in
     * the request body, and `/oauth/userinfo`, where the client is the one the
     * bearer token was minted for.
     */
    public function __invoke(): ?Client
    {
        $fromToken = request()->user()?->token()?->client_id;

        $clientId = $fromToken ?? request()->input('client_id');

        return $clientId === null ? null : Client::query()->find($clientId);
    }
}
