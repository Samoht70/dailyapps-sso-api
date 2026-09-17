<?php

namespace Technical\Audit\Rest\Controls;

use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;
use Technical\Audit\Models\SecurityEvent;
use Technical\Permissions\Enums\Permission;
use Technical\Permissions\Perimeters\OperatorPerimeter;
use Technical\Permissions\Perimeters\OwnOrganizationPerimeter;

class SecurityEventControl extends Control
{
    protected string $model = SecurityEvent::class;

    /**
     * Read only, and never past the caller's own organization: the journal of a
     * customer is not the business of another.
     *
     * @return array<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            OperatorPerimeter::requiring(['view' => Permission::ReadSecurityEvents]),
            OwnOrganizationPerimeter::administering(['view']),
        ];
    }
}
