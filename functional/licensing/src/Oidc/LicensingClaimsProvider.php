<?php

namespace Functional\Licensing\Oidc;

use Functional\Licensing\Models\ApplicationAccess;
use Functional\Users\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Technical\Oidc\Contracts\ClaimsProvider;
use Technical\Oidc\Models\Client;

class LicensingClaimsProvider implements ClaimsProvider
{
    /**
     * Roles are read for the calling application alone. Answering with what the
     * user holds elsewhere would hand every application the rights map of all
     * the others.
     *
     * @return array<string, mixed>
     */
    public function claimsFor(Authenticatable $user, Client $client): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return [
            'organization' => [
                'id' => $user->organization->getKey(),
                'name' => $user->organization->name,
            ],
            'roles' => $this->rolesOn($user, $client),
        ];
    }

    /** @return list<string> */
    private function rolesOn(User $user, Client $client): array
    {
        $application = $client->application;

        if ($application === null) {
            return [];
        }

        return ApplicationAccess::query()
            ->where('user_id', $user->getKey())
            ->where('application_id', $application->getKey())
            ->with('roles:id,key')
            ->get()
            ->flatMap(fn (ApplicationAccess $access): array => $access->roles->pluck('key')->all())
            ->values()
            ->all();
    }
}
