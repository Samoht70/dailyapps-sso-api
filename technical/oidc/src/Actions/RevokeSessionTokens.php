<?php

namespace Technical\Oidc\Actions;

use Functional\Catalog\Models\Application;
use Laravel\Passport\Passport;
use Technical\Oidc\Models\SsoSession;

class RevokeSessionTokens
{
    /**
     * Revoking in the database is not enough on its own — an access_token is a
     * JWT an application validates locally against the JWKS and never asks
     * about. That is why the push exists; this closes the refresh path.
     */
    public function __invoke(SsoSession $session, ?Application $only = null): int
    {
        $tokens = Passport::token()->newQuery()
            ->where('sso_session_id', $session->getKey())
            ->where('revoked', false)
            ->when($only, fn ($query) => $query->where('client_id', $only->oauth_client_id));

        $identifiers = $tokens->pluck('id');

        Passport::refreshToken()->newQuery()
            ->whereIn('access_token_id', $identifiers)
            ->update(['revoked' => true]);

        return Passport::token()->newQuery()
            ->whereIn('id', $identifiers)
            ->update(['revoked' => true]);
    }
}
