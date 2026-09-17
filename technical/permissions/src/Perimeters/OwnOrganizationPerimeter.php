<?php

namespace Technical\Permissions\Perimeters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Perimeters\Perimeter;

class OwnOrganizationPerimeter extends Perimeter
{
    /**
     * An administrator of a customer never reaches past their own organization,
     * in reading as in writing.
     *
     * @param  list<string>  $methods
     */
    public static function administering(array $methods, string $foreignKey = 'organization_id'): static
    {
        return static::new()
            ->allowed(fn (Model $user, string $method): bool => $user->isOrganizationAdmin()
                && in_array($method, $methods, true))
            ->should(fn (Model $user, Model $model): bool => $model->{$foreignKey} === $user->organization_id)
            ->query(fn (Builder $query, Model $user): Builder => $query->where($foreignKey, $user->organization_id));
    }
}
