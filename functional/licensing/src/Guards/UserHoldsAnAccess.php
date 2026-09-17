<?php

namespace Functional\Licensing\Guards;

use Functional\Licensing\Models\ApplicationAccess;
use Illuminate\Contracts\Auth\Authenticatable;
use Technical\Oidc\Contracts\AuthorizationGuard;
use Technical\Oidc\Enums\RefusalReason;
use Technical\Oidc\Models\Client;

class UserHoldsAnAccess implements AuthorizationGuard
{
    public function refuse(Authenticatable $user, Client $client): ?RefusalReason
    {
        $application = $client->application;

        if ($application === null) {
            return null;
        }

        $granted = ApplicationAccess::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('application_id', $application->getKey())
            ->exists();

        return $granted ? null : RefusalReason::NoAccess;
    }
}
