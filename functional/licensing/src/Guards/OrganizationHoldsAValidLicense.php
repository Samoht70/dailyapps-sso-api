<?php

namespace Functional\Licensing\Guards;

use Functional\Licensing\Models\License;
use Functional\Users\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Technical\Oidc\Contracts\AuthorizationGuard;
use Technical\Oidc\Enums\RefusalReason;
use Technical\Oidc\Models\Client;

class OrganizationHoldsAValidLicense implements AuthorizationGuard
{
    public function refuse(Authenticatable $user, Client $client): ?RefusalReason
    {
        $application = $client->application;

        if (! $user instanceof User || $application === null) {
            return RefusalReason::NoLicense;
        }

        $held = License::query()
            ->where('organization_id', $user->organization_id)
            ->where('application_id', $application->getKey())
            ->valid()
            ->exists();

        return $held ? null : RefusalReason::NoLicense;
    }
}
