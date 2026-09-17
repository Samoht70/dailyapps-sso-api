<?php

namespace Functional\Organizations\Rest\Controls;

use Functional\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;
use Technical\Permissions\Enums\Permission;
use Technical\Permissions\Perimeters\OperatorPerimeter;

class OrganizationControl extends Control
{
    protected string $model = Organization::class;

    /** @return array<Perimeter> */
    protected function perimeters(): array
    {
        return [
            OperatorPerimeter::requiring([
                'view' => Permission::DeclareOrganization,
                'create' => Permission::DeclareOrganization,
                'update' => Permission::DeclareOrganization,
                'delete' => Permission::DeclareOrganization,
            ]),
            Perimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $method === 'view')
                ->should(fn (Model $user, Model $model): bool => $model->getKey() === $user->organization_id)
                ->query(fn (Builder $query, Model $user): Builder => $query->whereKey($user->organization_id)),
        ];
    }
}
