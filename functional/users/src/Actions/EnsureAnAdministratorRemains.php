<?php

namespace Functional\Users\Actions;

use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Exceptions\LastAdministrator;
use Functional\Users\Models\User;

class EnsureAnAdministratorRemains
{
    /**
     * Applied both when disabling an account and when demoting it: an
     * organization with nobody left to administer it can only be repaired by
     * the operator, which is exactly the situation to avoid.
     *
     * @throws LastAdministrator
     */
    public function __invoke(User $account): void
    {
        if (! $account->isOrganizationAdmin() || $account->status !== UserStatus::Active) {
            return;
        }

        $others = User::query()
            ->where('organization_id', $account->organization_id)
            ->whereKeyNot($account->getKey())
            ->where('organization_role', OrganizationRole::Admin)
            ->where('status', UserStatus::Active)
            ->exists();

        if (! $others) {
            throw LastAdministrator::of($account->organization);
        }
    }
}
