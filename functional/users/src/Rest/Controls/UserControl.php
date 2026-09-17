<?php

namespace Functional\Users\Rest\Controls;

use Functional\Users\Models\User;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;
use Technical\Permissions\Enums\Permission;
use Technical\Permissions\Perimeters\OperatorPerimeter;
use Technical\Permissions\Perimeters\OwnOrganizationPerimeter;
use Technical\Permissions\Perimeters\OwnRowPerimeter;

class UserControl extends Control
{
    protected string $model = User::class;

    /** @return array<Perimeter> */
    protected function perimeters(): array
    {
        return [
            OperatorPerimeter::requiring([
                'view' => Permission::InviteUser,
                'create' => Permission::InviteUser,
                'update' => Permission::InviteUser,
                'delete' => Permission::InviteUser,
            ]),
            OwnOrganizationPerimeter::administering(['view', 'create', 'update', 'delete']),
            OwnRowPerimeter::reading(),
        ];
    }
}
