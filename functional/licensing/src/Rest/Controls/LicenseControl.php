<?php

namespace Functional\Licensing\Rest\Controls;

use Functional\Licensing\Models\License;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;
use Technical\Permissions\Enums\Permission;
use Technical\Permissions\Perimeters\OperatorPerimeter;
use Technical\Permissions\Perimeters\OwnOrganizationPerimeter;

class LicenseControl extends Control
{
    protected string $model = License::class;

    /** @return array<Perimeter> */
    protected function perimeters(): array
    {
        return [
            OperatorPerimeter::requiring([
                'view' => Permission::AttachLicense,
                'create' => Permission::AttachLicense,
                'update' => Permission::AttachLicense,
                'delete' => Permission::AttachLicense,
            ]),
            OwnOrganizationPerimeter::administering(['view']),
        ];
    }
}
