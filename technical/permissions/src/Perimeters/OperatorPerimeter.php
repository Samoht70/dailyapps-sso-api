<?php

namespace Technical\Permissions\Perimeters;

use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Perimeters\Perimeter;
use Technical\Permissions\Enums\Permission;

class OperatorPerimeter extends Perimeter
{
    /**
     * The operator sees everything, but only through a permission: a role name
     * is a string that changes meaning the day the business does, and a test on
     * it goes quietly false.
     *
     * @param  array<string, Permission>  $permissions  method to the permission it demands
     */
    public static function requiring(array $permissions): static
    {
        return static::new()
            ->allowed(fn (Model $user, string $method): bool => $user->organization->kind->isOperator()
                && isset($permissions[$method])
                && $user->can($permissions[$method]->value));
    }
}
