<?php

namespace Functional\Users\Actions;

use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Models\User;

class ChangeOrganizationRole
{
    public function __construct(private readonly EnsureAnAdministratorRemains $ensureAnAdministratorRemains) {}

    public function __invoke(User $account, OrganizationRole $role): User
    {
        if ($role === OrganizationRole::Member) {
            ($this->ensureAnAdministratorRemains)($account);
        }

        $account->forceFill(['organization_role' => $role])->save();

        return $account->fresh();
    }
}
