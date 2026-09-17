<?php

namespace Technical\Oidc\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use League\OAuth2\Server\Exception\OAuthServerException;
use Technical\Oidc\AuthorizationGuards;
use Technical\Oidc\Models\Client;

class AuthorizeUser
{
    public function __construct(private readonly AuthorizationGuards $guards) {}

    /**
     * @throws OAuthServerException carrying the machine-readable reason, so the
     *                              application can tell a cut-off account from a
     *                              missing licence from a missing access
     */
    public function __invoke(Authenticatable $user, Client $client, ?string $redirectUri = null): void
    {
        $reason = $this->guards->firstRefusal($user, $client);

        if ($reason === null) {
            return;
        }

        throw OAuthServerException::accessDenied($reason->value, $redirectUri);
    }
}
