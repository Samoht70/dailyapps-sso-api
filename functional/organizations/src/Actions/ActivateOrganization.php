<?php

namespace Functional\Organizations\Actions;

use Functional\Organizations\Models\Organization;

class ActivateOrganization
{
    /**
     * Accesses granted before the suspension become effective again without
     * being granted anew — the suspension froze them, it did not drop them.
     */
    public function __invoke(Organization $organization): Organization
    {
        $organization->activate();

        return $organization->fresh();
    }
}
