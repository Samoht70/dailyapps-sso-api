<?php

namespace Functional\Catalog\Listeners;

use Functional\Catalog\Models\ApplicationRole;
use Illuminate\Support\Facades\DB;

class DetachRolesOnApplicationRoleDeleting
{
    /**
     * Detaching through a listener rather than a database cascade: a cascade
     * bypasses Eloquent, so the listeners of the child rows never fire.
     */
    public function handle(ApplicationRole $role): void
    {
        DB::table('application_access_role')
            ->where('application_role_id', $role->getKey())
            ->orderBy('application_access_id')
            ->cursor()
            ->each(fn (object $row) => DB::table('application_access_role')
                ->where('application_role_id', $role->getKey())
                ->where('application_access_id', $row->application_access_id)
                ->delete());
    }
}
