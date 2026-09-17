<?php

namespace Functional\Licensing\Rest\Controls;

use Functional\Licensing\Models\ApplicationAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;
use Technical\Permissions\Enums\Permission;
use Technical\Permissions\Perimeters\OperatorPerimeter;

class ApplicationAccessControl extends Control
{
    protected string $model = ApplicationAccess::class;

    /**
     * An access carries no organization of its own — it borrows the one of the
     * account it was granted to, so the perimeter reaches through the user.
     *
     * @return array<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            OperatorPerimeter::requiring([
                'view' => Permission::GrantApplicationAccess,
                'create' => Permission::GrantApplicationAccess,
                'update' => Permission::GrantApplicationAccess,
                'delete' => Permission::GrantApplicationAccess,
            ]),
            Perimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user->isOrganizationAdmin())
                ->should(fn (Model $user, Model $model): bool => $model->user->organization_id === $user->organization_id)
                ->query(fn (Builder $query, Model $user): Builder => $query->whereHas(
                    'user',
                    fn (Builder $account) => $account->where('organization_id', $user->organization_id),
                )),
            Perimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $method === 'view')
                ->should(fn (Model $user, Model $model): bool => $model->user_id === $user->getKey())
                ->query(fn (Builder $query, Model $user): Builder => $query->where('user_id', $user->getKey())),
        ];
    }
}
