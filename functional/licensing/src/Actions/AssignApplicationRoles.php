<?php

namespace Functional\Licensing\Actions;

use Functional\Catalog\Models\ApplicationRole;
use Functional\Licensing\Exceptions\RoleFromAnotherApplication;
use Functional\Licensing\Models\ApplicationAccess;

class AssignApplicationRoles
{
    /**
     * @param  list<string>  $roleKeys
     *
     * @throws RoleFromAnotherApplication when a key names a role of another application
     */
    public function __invoke(ApplicationAccess $access, array $roleKeys): ApplicationAccess
    {
        if ($roleKeys === []) {
            return $access;
        }

        $roles = ApplicationRole::query()->whereIn('key', $roleKeys)->get();

        foreach ($roles as $role) {
            if ($role->application_id !== $access->application_id) {
                throw RoleFromAnotherApplication::is($role);
            }
        }

        $access->roles()->sync($roles->modelKeys());

        return $access;
    }
}
