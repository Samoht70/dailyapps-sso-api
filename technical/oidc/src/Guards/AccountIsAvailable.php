<?php

namespace Technical\Oidc\Guards;

use Functional\Users\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Technical\Oidc\Contracts\AuthorizationGuard;
use Technical\Oidc\Enums\RefusalReason;
use Technical\Oidc\Models\Client;

class AccountIsAvailable implements AuthorizationGuard
{
    public function refuse(Authenticatable $user, Client $client): ?RefusalReason
    {
        if (! $user instanceof User) {
            return RefusalReason::AccountUnavailable;
        }

        if (! $user->canAuthenticate() || ! $user->organization->isActive()) {
            return RefusalReason::AccountUnavailable;
        }

        return null;
    }
}
